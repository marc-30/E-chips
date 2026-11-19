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

  function locate(force) {
    return new Promise(function (resolve) {
      if (!('geolocation' in navigator)) return resolve(read());
      var cached = read();
      if (!force && cached && cached.label && Date.now() - cached.t < 6 * 3600e3) return resolve(cached);
      navigator.geolocation.getCurrentPosition(function (pos) {
        var lat = +pos.coords.latitude.toFixed(5), lng = +pos.coords.longitude.toFixed(5);
        var base = { lat: lat, lng: lng, label: '', t: Date.now() };
        write(base);
        reverse(lat, lng).then(function (l) { base.label = l; write(base); resolve(base); }, function () { resolve(base); });
      }, function () { resolve(read()); }, { enableHighAccuracy: false, timeout: 9000, maximumAge: 600000 });
    });
  }

  window.eChipsGeo = { get: read, locate: locate };
  // on laisse la page s'afficher avant de déclencher la demande d'autorisation
  addEventListener('load', function () { setTimeout(function () { locate(false); }, 1800); });
})();
