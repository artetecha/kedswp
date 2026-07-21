const test = require('node:test');
const assert = require('node:assert');
const path = require('path');

// Simulate the browser global BEFORE requiring so the script's synchronous
// top-level can attach to window (jQuery stays undefined here, mimicking the
// Google Maps async loader firing before jQuery(document).ready).
global.window = {};

// Source file must be node-requirable (guarded jQuery call) and export pure helpers.
const helpers = require(path.join(__dirname, '..', '..', 'src', 'assets', 'public', 'ff_address_autocomplete.js'));

test('fluentform_gmap_callback is defined synchronously (before jQuery/DOM ready)', () => {
    // The Google loader callback must exist even if it fires before
    // jQuery(document).ready — otherwise: "fluentform_gmap_callback is not a function".
    assert.strictEqual(typeof global.window.fluentform_gmap_callback, 'function');
    // Calling it before init is wired must not throw (it queues until ready).
    assert.doesNotThrow(() => global.window.fluentform_gmap_callback());
});

test('exports the pure helpers', () => {
    assert.strictEqual(typeof helpers.normalizeNewPlace, 'function');
    assert.strictEqual(typeof helpers.placesApiNewEnabled, 'function');
    assert.strictEqual(typeof helpers.advancedMarkerEnabled, 'function');
});

test('placesApiNewEnabled only true when flag is exactly "yes"', () => {
    assert.strictEqual(helpers.placesApiNewEnabled({ places_api_new: 'yes' }), true);
    assert.strictEqual(helpers.placesApiNewEnabled({ places_api_new: 'no' }), false);
    assert.strictEqual(helpers.placesApiNewEnabled({}), false);
    assert.strictEqual(helpers.placesApiNewEnabled(undefined), false);
});

test('advancedMarkerEnabled requires new api + advanced flag + map id', () => {
    assert.strictEqual(helpers.advancedMarkerEnabled({ places_api_new: 'yes', advanced_marker: 'yes', map_id: 'MAP123' }), true);
    // missing map id
    assert.strictEqual(helpers.advancedMarkerEnabled({ places_api_new: 'yes', advanced_marker: 'yes', map_id: '' }), false);
    // advanced off
    assert.strictEqual(helpers.advancedMarkerEnabled({ places_api_new: 'yes', advanced_marker: 'no', map_id: 'MAP123' }), false);
    // new api off
    assert.strictEqual(helpers.advancedMarkerEnabled({ places_api_new: 'no', advanced_marker: 'yes', map_id: 'MAP123' }), false);
    assert.strictEqual(helpers.advancedMarkerEnabled(undefined), false);
});

test('normalizeNewPlace maps new Place fields to the legacy shape', () => {
    const fakeLatLng = { lat: () => 40.1, lng: () => -75.2 };
    const newPlace = {
        displayName: 'Independence Hall',
        formattedAddress: '520 Chestnut St, Philadelphia, PA 19106',
        addressComponents: [
            { longText: '520', shortText: '520', types: ['street_number'] },
            { longText: 'Chestnut Street', shortText: 'Chestnut St', types: ['route'] },
            { longText: 'Philadelphia', shortText: 'Philadelphia', types: ['locality'] },
            { longText: 'United States', shortText: 'US', types: ['country'] },
        ],
        location: fakeLatLng,
        viewport: { foo: 'bar' },
    };

    const normalized = helpers.normalizeNewPlace(newPlace);

    assert.strictEqual(normalized.name, 'Independence Hall');
    assert.strictEqual(normalized.formatted_address, '520 Chestnut St, Philadelphia, PA 19106');
    assert.strictEqual(normalized.address_components.length, 4);
    assert.deepStrictEqual(normalized.address_components[0], { long_name: '520', short_name: '520', types: ['street_number'] });
    assert.deepStrictEqual(normalized.address_components[3], { long_name: 'United States', short_name: 'US', types: ['country'] });
    assert.strictEqual(normalized.geometry.location, fakeLatLng);
    assert.strictEqual(normalized.geometry.viewport, newPlace.viewport);
    assert.strictEqual(normalized.latLng, fakeLatLng);
});

test('normalizeNewPlace tolerates a place with no location/components', () => {
    const normalized = helpers.normalizeNewPlace({});
    assert.deepStrictEqual(normalized.address_components, []);
    assert.strictEqual(normalized.geometry, null);
    assert.strictEqual(normalized.latLng, null);
    assert.strictEqual(normalized.name, '');
});
