/* e-CHIPS — localisation proposée par défaut pour la livraison.
   Dès l'arrivée sur le site, on demande la position (le navigateur affiche sa propre autorisation),
   puis on la convertit en adresse lisible (OpenStreetMap Nominatim). Résultat gardé dans localStorage
   ("echips-loc") et réutilisé au moment de la commande ; le client peut toujours la modifier. */
(function () {
  var KEY = 'echips-loc';
  function read() { try { return JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) { return null; } }
  function write(v) { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) {} }

  function label(a) {
    var parts = [a.neighbourhood || a.suburb || a.quarter || a.road, a.city_district, a.city || a.town || a.village || a.municipality, a.state];
    var out = [];
    parts.forEach(function (p) { if (p && out.indexOf(p) < 0) out.push(p); });
    return out.slice(0, 3).join(', ');
  }

  function reverse(lat, lng) {
    var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=17&addressdetails=1&accept-language=fr&lat=' + lat + '&lon=' + lng;
    return fetch(url).then(function (r) { return r.json(); }).then(function (j) {
      return (j && j.address && label(j.address)) || (j && j.display_name) || '';
    });
  }

  // Repli : position approximative via l'adresse IP (si le GPS est refusé / indisponible)
  function byIP() {
    return fetch('https://ipwho.is/').then(function (r) { return r.json(); }).then(function (j) {
      if (!j || !j.success) return null;
      var l = [j.city, j.region].filter(Boolean).join(', ');
      return { lat: +(+j.latitude).toFixed(3), lng: +(+j.longitude).toFixed(3), label: l, approx: true, t: Date.now() };
    }).catch(function () { return null; });
  }

  var lastError = 0;
  function gps(opts) {
    return new Promise(function (res) {
      navigator.geolocation.getCurrentPosition(function (p) { res({ pos: p }); }, function (e) { res({ err: e && e.code || 2 }); }, opts);
    });
  }

  function locate(force) {
    var cached = read();
    if (!force && cached && cached.label && !cached.approx && Date.now() - cached.t < 6 * 3600e3) return Promise.resolve(cached);
    var start = ('geolocation' in navigator)
      ? gps({ enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 }).then(function (r) {
          if (r.err && r.err !== 1) return gps({ enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }); // 2e essai, GPS précis
          return r;
        })
      : Promise.resolve({ err: 2 });
    return start.then(function (r) {
      if (r.pos) {
        lastError = 0;
        var lat = +r.pos.coords.latitude.toFixed(5), lng = +r.pos.coords.longitude.toFixed(5);
        var base = { lat: lat, lng: lng, label: '', t: Date.now() };
        write(base);
        return reverse(lat, lng).then(function (l) { base.label = l; write(base); return base; }, function () { return base; });
      }
      lastError = r.err || 2;
      return byIP().then(function (ip) { if (ip) write(ip); return ip || (cached && cached.label ? cached : null); });
    });
  }

  window.eChipsGeo = { get: read, locate: locate, error: function () { return lastError; } };
  // on laisse la page s'afficher avant de déclencher la demande d'autorisation
  addEventListener('load', function () { setTimeout(function () { locate(false); }, 1800); });
})();
