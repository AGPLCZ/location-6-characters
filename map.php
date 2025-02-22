<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Mapa svobodných škol</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Mapbox -->
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js"></script>
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css" rel="stylesheet" />
  <!-- Bootstrap -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
  <script src="generateMapData.php"></script>



  <style>
    /* Reset a základní styl */
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    /* Mapa přes celé okno */
    #map {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 100%;
      z-index: -1;
    }

    /* Kontejner pro formuláře a checkbox */
    .header-container {
      position: absolute;
      top: 5px;
      left: 5px;
      background: rgba(58, 58, 58, 0.9);
      padding: 5px;
      border-radius: 5px;
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      max-width: 80%;
    }

    .header-container h1 {
      font-size: 1.2rem;
      margin: 0;
      text-shadow: 0 0 5px #ffffff;
    }

    .header-container input {
      width: 100%;
      margin-top: 3px;
      padding: 3px;
      font-size: 0.8rem;
      border: none;
      border-radius: 3px;
    }

    .header-container button {
      margin-top: 3px;
      width: 100%;
      font-size: 0.8rem;
      padding: 3px;
    }

    .checkbox-container {
      display: flex;
      align-items: center;
      margin-top: 5px;
    }

    .checkbox-container input {
      margin-right: 5px;
    }

    .checkbox-container label {
      font-size: 0.9rem;
    }

    /* Kontejner pro našeptávač – bude přímo pod adresním inputem */
    .suggestions-container {
      display: none;
      /* Ve výchozím stavu skryté */
      background-color: rgba(58, 58, 58, 0.9);
      color: #fff;
      margin-top: 5px;
      /* Trochu místa pod inputem */
      max-width: 100%;
      /* Pojme šířku formuláře */
      max-height: 200px;
      /* Můžeš upravit dle libosti */
      overflow-y: auto;
      /* Rolování, když je hodně návrhů */
      border-radius: 5px;
      padding: 5px;
    }

    /* Jednotlivé položky návrhů */
    .suggestions-container div {
      padding: 3px 0;
      cursor: pointer;
    }

    .suggestions-container div:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }
  </style>
</head>

<body>


  <!-- Mapa -->
  <div id="map"></div>

  <!-- Formuláře a checkbox vlevo nahoře -->
  <div class="header-container">
    <h1>Svobodné školy</h1>

    <!-- Vyhledání podle 8znakového kódu -->
    <input type="text" id="searchInput" placeholder="GPS-8 lokalizace" />
    <button onclick="searchLocation()" class="btn btn-danger">Hledat</button>

    <!-- Vyhledání podle adresy (s našeptávačem) -->
    <input type="text" id="addressInput" placeholder="Zadejte adresu" onkeyup="suggestAddresses()" />
    <button onclick="searchByAddress()" class="btn btn-danger">Hledat</button>

    <!-- Checkbox pro přidání školy kliknutím na mapu -->
    <div class="checkbox-container">
      <input type="checkbox" id="addLocationCheckbox" />
      <label for="addLocationCheckbox">Přidat školu</label>
    </div>

    <!-- Kontejner pro našeptávač: zobrazí se až při psaní do pole -->
    <div id="suggestions" class="suggestions-container"></div>
  </div>

  <script>
    // Klíč pro Mapbox
    mapboxgl.accessToken =
      "pk.eyJ1IjoiYWdwbCIsImEiOiJjbG1rY3lqdWswMWliMnJuenpndHpnMmh6In0.ZAg3F_H8uxL9jC5h8f41Iw";

    // Vytvoření mapy
    let popup = new mapboxgl.Popup({
      closeButton: false,
      closeOnClick: true,
    });
    const map = new mapboxgl.Map({
      container: "map",
      style: "mapbox://styles/mapbox/dark-v10",
      center: [15, 50],
      zoom: 6,
    });

    window.onload = function () {
    if (typeof presetLocations === "undefined" || !Array.isArray(presetLocations)) {
        console.error("Chyba: presetLocations není správně načteno.");
        return;
    }
    console.log("Načteno presetLocations:", presetLocations);
    addPresetLocationsToMap(); // Zavoláme funkci až po načtení `presetLocations`
};





    // Pro generování a dekódování GPS-8
    const charset =
      "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";



      function getGeolocationFromCharacters(characters) {
    if (characters.length !== 8) {
        return null;
    }
    let latFrac =
        charset.indexOf(characters[0]) * charset.length ** 3 +
        charset.indexOf(characters[1]) * charset.length ** 2 +
        charset.indexOf(characters[2]) * charset.length +
        charset.indexOf(characters[3]);
    let lonFrac =
        charset.indexOf(characters[4]) * charset.length ** 3 +
        charset.indexOf(characters[5]) * charset.length ** 2 +
        charset.indexOf(characters[6]) * charset.length +
        charset.indexOf(characters[7]);

    const latitude = (latFrac / charset.length ** 4) * 180 - 90;
    const longitude = (lonFrac / charset.length ** 4) * 360 - 180;
    return { latitude, longitude };
}


    //----------------------------------------------------------------
    // 1) Přidání přednastavených lokací na mapu (načítání z databáze)
    //----------------------------------------------------------------
    function addPresetLocationsToMap() {
    console.log("Načítání škol:", presetLocations); // Debugging

    presetLocations.forEach(location => {
      if (!location.latitude || !location.longitude) {
    let geoCoordinates = getGeolocationFromCharacters(location.locationCode);
    if (geoCoordinates) {
        location.latitude = geoCoordinates.latitude;
        location.longitude = geoCoordinates.longitude;
    } else {
        console.warn(`Škola ${location.name} nemá platné souřadnice a nebylo možné je dekódovat.`);
        return;
    }
}

   // Slovník pro čitelnější popisky
const labels = {
    school_name: "Jméno školy",
    school_id: "ID",
    locationCode: "Kód lokace",
    address: "Adresa",
    web: "Web:",
    tel: "Telefon",
    licence: "Zapsána v rejstříku škol",
    vek: "Typ školy/školky",
    dem_rozhodovani: "Demokratické rozhodování",
    resp_komunikace: "Respektující komunikace",
    resp_pristup: "Respektující přístup",
    pov_ucivo: "Povinné učivo",
    hodnoceni: "Probíhá hodnocení",
    vyuc_hodiny: "Vyučovací hodiny",
    pov_dochazka: "Povinná docházka",
    odchod_budova: "Možnost odcházet z budovy",
    pov_cinnosti: "Povinné činnosti",
    role_dospeleho: "Role dospělého",
    pravidla_hranice: "Pravidla a hranice",
    soc_interakce: "Sociální interakce",
    zpusob_prace: "Způsob práce",
    org_prostoru: "Organizace prostoru",
    indiv_studium: "Možnost individuálního studia",
    spolucast_deti: "Spoluúčast dětí na chodu školy",
    stravovani: "Stravování",
    stat_osnovy: "Přítomnost státních osnov"
};

// **Ověření, zda `locationCode` existuje a je správně načten**
if (!location.locationCode) {
    console.warn(`LocationCode pro školu ${location.school_name} není dostupný.`);
}

// **Vytvoření pop-upu s čitelnými popisky**
let popupHTML = `<div><strong>${labels.school_name}:</strong> ${location.school_name}</div>`;

// **Zobrazení `locationCode` jako druhý řádek (pokud existuje)**
if (location.locationCode) {
    popupHTML += `<div><strong>${labels.locationCode}:</strong> ${location.locationCode}</div>`;
}

// **Přidání ostatních atributů, vynechání `school_name` a `locationCode`**
for (let key in location) {
    if (!['latitude', 'longitude', 'school_name', 'locationCode'].includes(key) && location[key]) {
        let label = labels[key] || key.replace('_', ' '); // Použij čitelný popisek, jinak uprav název sloupce
        popupHTML += `<div><strong>${label}:</strong> ${location[key]}</div>`;
    }
}

        let popup = new mapboxgl.Popup({ offset: 25 }).setHTML(popupHTML);

        new mapboxgl.Marker()
            .setLngLat([parseFloat(location.longitude), parseFloat(location.latitude)])
            .setPopup(popup)
            .addTo(map);
    });
}





    //----------------------------------------------------------------
    // 2) Kliknutí na mapu => přidání nové lokace, pokud je checkbox zaškrtnut
    //----------------------------------------------------------------
    map.on("click", function (e) {
      const addLocationChecked = document.getElementById(
        "addLocationCheckbox"
      ).checked;
      if (!addLocationChecked) return;

      const { lat, lng } = e.lngLat;
      getAddressFromCoordinates(lat, lng, function (address) {
        if (address) {
          const characters = getCharactersFromGeolocation({
            latitude: lat,
            longitude: lng,
          });
          const popupContent = `
                        <div>
                            <div><strong>Adresa místa:</strong> ${address}</div>
                            <div><strong>Označení místa:</strong>
                            <span id="locationCode" style="cursor:pointer; text-decoration: underline;">${characters}</span></div>
                            <div style="text-decoration: underline;"><a href="editMap.php" target="blank">Přidat místo</a></div>
                        </div>
                    `;
          popup.setLngLat([lng, lat]).setHTML(popupContent).addTo(map);

          // Zobrazíme kód do pole searchInput
          document.getElementById("searchInput").value = characters;
        } else {
          alert("Nepodařilo se získat adresu.");
        }
      });
    });

    //----------------------------------------------------------------
    // 3) Vyhledávání podle GPS-8
    //----------------------------------------------------------------
    function searchLocation() {
      const searchInput = document.getElementById("searchInput").value;
      if (searchInput.length === 8) {
        const geolocation = getGeolocationFromCharacters(searchInput);
        if (geolocation) {
          const { latitude, longitude } = geolocation;
          new mapboxgl.Marker().setLngLat([longitude, latitude]).addTo(map);
          map.flyTo({ center: [longitude, latitude], zoom: 18 });
        } else {
          alert("Nepodařilo se převést řetězec na geolokaci.");
        }
      } else {
        alert("Vstup musí mít právě 8 znaků.");
      }
    }

    //----------------------------------------------------------------
    // 4) Vyhledávání podle adresy
    //----------------------------------------------------------------
    function searchByAddress() {
      const addressInput = document.getElementById("addressInput").value;
      const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(
        addressInput
      )}.json?access_token=${mapboxgl.accessToken}`;

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          if (data && data.features && data.features.length) {
            const location = data.features[0].geometry.coordinates;
            new mapboxgl.Marker().setLngLat(location).addTo(map);
            map.flyTo({
              center: location,
              zoom: 18,
            });
          } else {
            alert("Nepodařilo se najít adresu.");
          }
        })
        .catch((error) => {
          console.error("Error fetching location:", error);
          alert("Chyba při vyhledávání adresy.");
        });
    }

    //----------------------------------------------------------------
    // 5) Našeptávač adres
    //----------------------------------------------------------------
    function suggestAddresses() {
      const input = document.getElementById("addressInput").value;
      const suggestionsBox = document.getElementById("suggestions");

      if (!input) {
        closeSuggestions();
        return;
      }
      const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(
        input
      )}.json?limit=5&access_token=${mapboxgl.accessToken}`;

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          if (data && data.features && data.features.length) {
            let suggestionsHtml = "";
            for (let feature of data.features) {
              suggestionsHtml += `<div onclick="selectSuggestion('${feature.place_name}', [${feature.geometry.coordinates}])">${feature.place_name}</div>`;
            }
            suggestionsBox.innerHTML = suggestionsHtml;
            suggestionsBox.style.display = "block"; // Zobrazíme box
          } else {
            suggestionsBox.innerHTML = "<div>No suggestions found</div>";
            suggestionsBox.style.display = "block";
          }
        })
        .catch((error) => {
          console.error("Error fetching suggestions:", error);
        });
    }

    // Zavře našeptávač
    function closeSuggestions() {
      const suggestionsBox = document.getElementById("suggestions");
      suggestionsBox.innerHTML = "";
      suggestionsBox.style.display = "none";
    }

    // Vybrání některého z návrhů
    function selectSuggestion(name, coordinates) {
      document.getElementById("addressInput").value = name;
      closeSuggestions(); // schová nápovědu
      new mapboxgl.Marker().setLngLat(coordinates).addTo(map);
      map.flyTo({ center: coordinates, zoom: 18 });
    }

    // Kliknutí mimo nápovědu => zavřít
    document.addEventListener("click", function (event) {
      const input = document.getElementById("addressInput");
      const suggestions = document.getElementById("suggestions");
      if (
        event.target !== input &&
        event.target !== suggestions &&
        !suggestions.contains(event.target)
      ) {
        closeSuggestions();
      }
    });

    //----------------------------------------------------------------
    // 6) Kopírování do schránky
    //----------------------------------------------------------------
    function copyToClipboard(text) {
      const textarea = document.createElement("textarea");
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand("copy");
      document.body.removeChild(textarea);
    }

    // Pokud se popup otevře, můžeme napojit click pro kopírování
    popup.on("open", function () {
      const locationCodeElement = document.getElementById("locationCode");
      if (locationCodeElement) {
        locationCodeElement.addEventListener("click", function () {
          copyToClipboard(locationCodeElement.textContent);
          alert("Poloha zkopírována do schránky!");
        });
      }
    });

    //----------------------------------------------------------------
    // 7) Pomocné funkce: Kód -> souřadnice, Souřadnice -> kód
    //----------------------------------------------------------------
    // Dekódování 8znakového kódu (GPS-8) na souřadnice
    function getGeolocationFromCharacters(characters) {
      if (characters.length !== 8) {
        return null;
      }
      let latFrac =
        charset.indexOf(characters[0]) * charset.length ** 3 +
        charset.indexOf(characters[1]) * charset.length ** 2 +
        charset.indexOf(characters[2]) * charset.length +
        charset.indexOf(characters[3]);
      let lonFrac =
        charset.indexOf(characters[4]) * charset.length ** 3 +
        charset.indexOf(characters[5]) * charset.length ** 2 +
        charset.indexOf(characters[6]) * charset.length +
        charset.indexOf(characters[7]);

      const latitude = (latFrac / charset.length ** 4) * 180 - 90;
      const longitude = (lonFrac / charset.length ** 4) * 360 - 180;
      return { latitude, longitude };
    }

    // Zakódování souřadnic -> 8 znaků
    function getCharactersFromGeolocation(position) {
      let latitudeFraction = Math.round(
        ((position.latitude + 90) / 180) * charset.length ** 4
      );
      let longitudeFraction = Math.round(
        ((position.longitude + 180) / 360) * charset.length ** 4
      );

      const latChar1 =
        charset[Math.floor(latitudeFraction / charset.length ** 3)];
      latitudeFraction %= charset.length ** 3;
      const latChar2 =
        charset[Math.floor(latitudeFraction / charset.length ** 2)];
      latitudeFraction %= charset.length ** 2;
      const latChar3 = charset[Math.floor(latitudeFraction / charset.length)];
      const latChar4 = charset[latitudeFraction % charset.length];

      const lonChar1 =
        charset[Math.floor(longitudeFraction / charset.length ** 3)];
      longitudeFraction %= charset.length ** 3;
      const lonChar2 =
        charset[Math.floor(longitudeFraction / charset.length ** 2)];
      longitudeFraction %= charset.length ** 2;
      const lonChar3 =
        charset[Math.floor(longitudeFraction / charset.length)];
      const lonChar4 = charset[longitudeFraction % charset.length];

      return (
        latChar1 +
        latChar2 +
        latChar3 +
        latChar4 +
        lonChar1 +
        lonChar2 +
        lonChar3 +
        lonChar4
      );
    }

    // Získání adresy z lat/lng přes Mapbox geocoding
    function getAddressFromCoordinates(lat, lon, callback) {
      const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lon},${lat}.json?access_token=${mapboxgl.accessToken}`;
      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          if (data && data.features && data.features.length) {
            const address = data.features[0].place_name;
            callback(address);
          } else {
            callback(null);
          }
        })
        .catch((error) => {
          console.error("Error fetching address:", error);
          callback(null);
        });
    }
  </script>
</body>

</html>