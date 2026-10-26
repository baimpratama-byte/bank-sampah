(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };
  var rp = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };

  /* Sidebar */
  var shell = $('#shell');
  if (shell) {
    if (innerWidth >= 800) shell.classList.add('open');
    $('#burger').onclick = function () { shell.classList.toggle('open'); };
    $('#scrim').onclick = function () { shell.classList.remove('open'); };
  }

  /* Notifikasi */
  var bell = $('#bell');
  if (bell) {
    var np = $('#np');
    bell.onclick = function (e) {
      e.stopPropagation();
      var open = np.classList.toggle('hid') === false;
      bell.setAttribute('aria-expanded', open);
      var nb = $('#nb');
      if (open && nb) {
        fetch('api/notif_read.php', { method: 'POST', headers: { 'X-CSRF': $('meta[name=csrf]').content } })
          .then(function () { nb.remove(); });
      }
    };
    document.addEventListener('click', function (e) { if (!e.target.closest('.bw')) np.classList.add('hid'); });
  }

  /* Konfirmasi hapus */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  /* Carousel iklan */
  var ads = $('#ads');
  if (ads) {
    var tr = $('.ads-t', ads), dots = $$('.dot', ads), n = dots.length, i = 0, tm;
    var show = function (k) {
      i = (k + n) % n;
      tr.style.transform = 'translateX(-' + i * 100 + '%)';
      dots.forEach(function (d, j) { d.classList.toggle('on', j === i); });
    };
    var run = function () {
      clearInterval(tm);
      if (n > 1 && !matchMedia('(prefers-reduced-motion:reduce)').matches) tm = setInterval(function () { show(i + 1); }, 4000);
    };
    $$('.ads-b', ads).forEach(function (b) { b.onclick = function () { show(i + parseInt(b.dataset.d, 10)); run(); }; });
    dots.forEach(function (d, j) { d.onclick = function () { show(j); run(); }; });
    ads.onmouseenter = function () { clearInterval(tm); };
    ads.onmouseleave = run;
    run();
  }

  /* Pilihan alamat bertingkat: provinsi > kabupaten/kota > kecamatan > kelurahan/desa */
  if ($('.wil')) {
    var S = { prov: $('#provinsi'), kab: $('#kabupaten_kota'), kec: $('#kecamatan'), kel: $('#kelurahan') };
    var enc = encodeURIComponent;
    var api = function (q) {
      return fetch('api/wilayah.php?' + q).then(function (r) { return r.json(); }).catch(function () { return []; });
    };
    var xin = function (el) { return $('#' + el.id + '_x'); };
    var opt = function (v, t) { var o = document.createElement('option'); o.value = v; o.textContent = t; return o; };
    var setL = function (el, list) {
      var x = xin(el); x.classList.add('hid'); x.value = '';
      el.innerHTML = '';
      if (list === undefined) { el.appendChild(opt('', 'Pilih level di atasnya dulu')); el.disabled = true; return; }
      el.disabled = false;
      if (list === null || !list.length) {
        el.appendChild(opt('__m', 'Belum ada di daftar, ketik sendiri')); el.value = '__m'; x.classList.remove('hid'); return;
      }
      el.appendChild(opt('', 'Pilih'));
      list.forEach(function (t) { el.appendChild(opt(t, t)); });
      el.appendChild(opt('__m', 'Tidak ada di daftar, ketik sendiri'));
    };
    var choose = function (el, v) {
      if (!v) return;
      var has = [].some.call(el.options, function (o) { return o.value === v; });
      if (has) el.value = v; else { el.value = '__m'; xin(el).classList.remove('hid'); xin(el).value = v; }
    };
    var toggleX = function (el) { xin(el).classList.toggle('hid', el.value !== '__m'); };
    var loadKab = async function () {
      var p = S.prov.value;
      if (!p) { setL(S.kab); setL(S.kec); setL(S.kel); return; }
      if (p === '__m') { setL(S.kab, null); setL(S.kec, null); setL(S.kel, null); return; }
      var l = await api('l=k&p=' + enc(p)), kosong = !l.length;
      setL(S.kab, l); setL(S.kec, kosong ? null : undefined); setL(S.kel, kosong ? null : undefined);
    };
    var loadKec = async function () {
      var p = S.prov.value, k = S.kab.value;
      if (!k) { setL(S.kec); setL(S.kel); return; }
      if (k === '__m' || p === '__m') { setL(S.kec, null); setL(S.kel, null); return; }
      var l = await api('l=c&p=' + enc(p) + '&k=' + enc(k)), kosong = !l.length;
      setL(S.kec, l); setL(S.kel, kosong ? null : undefined);
    };
    var loadKel = async function () {
      var p = S.prov.value, k = S.kab.value, c = S.kec.value;
      if (!c) { setL(S.kel); return; }
      if (c === '__m' || k === '__m' || p === '__m') { setL(S.kel, null); return; }
      setL(S.kel, await api('l=l&p=' + enc(p) + '&k=' + enc(k) + '&c=' + enc(c)));
    };
    S.prov.onchange = function () { toggleX(S.prov); loadKab(); };
    S.kab.onchange = function () { toggleX(S.kab); loadKec(); };
    S.kec.onchange = function () { toggleX(S.kec); loadKel(); };
    S.kel.onchange = function () { toggleX(S.kel); };
    (async function () {
      setL(S.prov, await api('l=p')); setL(S.kab); setL(S.kec); setL(S.kel);
      var pv = S.prov.dataset.v;
      if (pv) {
        choose(S.prov, pv); await loadKab();
        choose(S.kab, S.kab.dataset.v); await loadKec();
        choose(S.kec, S.kec.dataset.v); await loadKel();
        choose(S.kel, S.kel.dataset.v);
      }
    })();
  }

  /* QR anggota (profil masyarakat) */
  var qb = $('#qrbox');
  if (qb) {
    if (window.QRCode) new QRCode(qb, { text: qb.dataset.kode, width: 176, height: 176, colorDark: '#14532d', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
    else qb.textContent = qb.dataset.kode;
  }

  /* Foto profil: kirim otomatis setelah memilih file */
  var fi = $('#fotoInput');
  if (fi) fi.onchange = function () { if (fi.files[0]) fi.form.submit(); };

  /* Total setoran langsung */
  var calc = function () {
    var tot = 0;
    $$('.berat').forEach(function (inp) {
      var b = parseFloat(String(inp.value).replace(',', '.')), s = b > 0 ? b * parseFloat(inp.dataset.harga) : 0;
      tot += s; $('#st' + inp.dataset.id).textContent = rp(s);
    });
    $('#tt').textContent = rp(tot);
  };
  if ($("#tt")) { $$(".berat").forEach(function (inp) { inp.oninput = calc; }); calc(); }

  /* Scan QR petugas */
  var scm = $('#scm');
  if (scm) {
    var stream = null, st = $('#scst'), vd = $('#scv'), cv = $('#scc');
    var found = function (txt) {
      if (!/^\s*(BS-?)?\d+\s*$/i.test(txt)) { st.textContent = 'QR tidak dikenali sebagai ID anggota. Coba lagi.'; return false; }
      $('#idm').value = txt.trim(); stop(); $('#cariForm').submit(); return true;
    };
    var stop = function () { if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; } scm.style.display = 'none'; };
    var tick = function () {
      if (!stream) return;
      if (vd.readyState === 4 && vd.videoWidth) {
        cv.width = vd.videoWidth; cv.height = vd.videoHeight;
        var x = cv.getContext('2d'); x.drawImage(vd, 0, 0);
        var d = x.getImageData(0, 0, cv.width, cv.height), r = window.jsQR && jsQR(d.data, d.width, d.height);
        if (r && r.data && found(r.data)) return;
      }
      requestAnimationFrame(tick);
    };
    $('#scanBtn').onclick = function () {
      scm.style.display = 'flex'; st.textContent = 'Mengaktifkan kamera...';
      if (!window.jsQR) { st.textContent = 'Pemindai belum termuat. Cek koneksi internet, atau unggah gambar QR.'; return; }
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { st.textContent = 'Kamera butuh HTTPS atau localhost. Unggah gambar QR atau ketik ID.'; return; }
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (s) {
        stream = s; vd.srcObject = s; vd.play(); st.textContent = 'Arahkan kamera ke QR anggota'; requestAnimationFrame(tick);
      }).catch(function () { st.textContent = 'Kamera tidak bisa dipakai (izin ditolak). Unggah gambar QR atau ketik ID.'; });
    };
    $('#scClose').onclick = stop;
    $('#scFile').onchange = function () {
      var f = this.files[0]; this.value = ''; if (!f) return;
      var fr = new FileReader();
      fr.onload = function () {
        var im = new Image();
        im.onload = function () {
          cv.width = im.width; cv.height = im.height;
          var x = cv.getContext('2d'); x.drawImage(im, 0, 0);
          var r = window.jsQR && jsQR(x.getImageData(0, 0, cv.width, cv.height).data, cv.width, cv.height);
          if (!(r && r.data && found(r.data))) st.textContent = 'QR tidak terbaca dari gambar itu.';
        };
        im.src = fr.result;
      };
      fr.readAsDataURL(f);
    };
  }
})();