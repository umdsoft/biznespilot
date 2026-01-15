<script setup>
import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    businessId: { type: [String, Number], required: true },
    adAccountId: { type: [String, Number], required: true },
    currency: { type: String, default: 'USD' },
});

// Wizard state
const currentStep = ref(1);
const totalSteps = 4;
const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const success = ref(null);

// Options loaded from API
const options = ref({
    objectives: [],
    countries: [],
    call_to_actions: [],
    pages: [],
    ad_account: null,
    lead_forms: [],
});

// Campaign data
const campaign = ref({
    name: '',
    objective: '',
});

// AdSet data
const adSet = ref({
    name: '',
    daily_budget: 5,
    optimization_goal: '',
    targeting: {
        geo_locations: {
            countries: ['UZ'],
            cities: [],
            regions: [],
        },
        age_min: 18,
        age_max: 65,
        genders: [1, 2],
        flexible_spec: [],
        exclusions: {},
    },
    placements: {
        automatic: true,
        facebook_feed: true,
        facebook_marketplace: true,
        facebook_video_feeds: true,
        facebook_stories: true,
        facebook_reels: true,
        facebook_right_column: false,
        facebook_search: false,
        facebook_instream_video: false,
        instagram_feed: true,
        instagram_stories: true,
        instagram_reels: true,
        instagram_explore: true,
        instagram_explore_home: false,
        instagram_shop: false,
        instagram_profile_feed: false,
        audience_network_native: false,
        audience_network_banner: false,
        audience_network_interstitial: false,
        audience_network_rewarded_video: false,
        messenger_inbox: false,
        messenger_stories: false,
        messenger_sponsored: false,
    },
    start_time: '',
    end_time: '',
    bid_strategy: 'LOWEST_COST_WITHOUT_CAP',
});

// Ad data
const ad = ref({
    name: '',
    page_id: '',
    primary_text: '',
    headline: '',
    description: '',
    link: '',
    call_to_action: 'LEARN_MORE',
    image_hash: '',
    image_preview: '',
    format: 'single_image',
    lead_form_id: '', // For lead campaigns
});

// Audience estimate
const audienceEstimate = ref({
    users_lower_bound: 0,
    users_upper_bound: 0,
    loading: false,
});

// Preview format
const previewFormat = ref('facebook_feed');

// Interest & Location search
const interestQuery = ref('');
const interestResults = ref([]);
const selectedInterests = ref([]);
const searchingInterests = ref(false);
const locationQuery = ref('');
const locationResults = ref([]);
const selectedLocations = ref([]);
const searchingLocations = ref(false);

// Map
const mapContainer = ref(null);
const map = ref(null);
const mapMarkers = ref([]);
const mapCircles = ref([]);
const showMap = ref(true);
const mapClickMode = ref('add'); // 'add', 'exclude', null
const isAddingLocation = ref(false);

// Custom locations with radius (like FB Ads Manager)
const customLocations = ref([]);
const excludedLocations = ref([]);
const defaultRadius = ref(25);
const radiusOptions = [10, 15, 20, 25, 30, 40, 50, 80, 100, 150, 200];

// Reverse geocoding cache
const geocodeCache = ref({});

// Country coordinates for map
const countryCoordinates = {
    'UZ': { lat: 41.377491, lng: 64.585262, name: "O'zbekiston", zoom: 5 },
    'KZ': { lat: 48.019573, lng: 66.923684, name: "Qozog'iston", zoom: 4 },
    'RU': { lat: 61.52401, lng: 105.318756, name: "Rossiya", zoom: 3 },
    'TJ': { lat: 38.861034, lng: 71.276093, name: "Tojikiston", zoom: 6 },
    'KG': { lat: 41.20438, lng: 74.766098, name: "Qirg'iziston", zoom: 6 },
    'TM': { lat: 38.969719, lng: 59.556278, name: "Turkmaniston", zoom: 5 },
    'AZ': { lat: 40.143105, lng: 47.576927, name: "Ozarbayjon", zoom: 6 },
    'GE': { lat: 42.315407, lng: 43.356892, name: "Gruziya", zoom: 7 },
    'TR': { lat: 38.963745, lng: 35.243322, name: "Turkiya", zoom: 5 },
    'AE': { lat: 23.424076, lng: 53.847818, name: "BAA", zoom: 6 },
    'US': { lat: 37.09024, lng: -95.712891, name: "AQSH", zoom: 3 },
    'GB': { lat: 55.378051, lng: -3.435973, name: "Buyuk Britaniya", zoom: 5 },
    'DE': { lat: 51.165691, lng: 10.451526, name: "Germaniya", zoom: 5 },
};

// Image upload
const imageFile = ref(null);
const uploadingImage = ref(false);

// Step names for display
const stepNames = ['Kampaniya', 'Auditoriya', 'Kreativ', 'Tasdiqlash'];

// Computed
const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return campaign.value.name && campaign.value.objective;
        case 2:
            return adSet.value.name && adSet.value.daily_budget >= 1 && adSet.value.optimization_goal;
        case 3:
            return ad.value.name && ad.value.page_id && ad.value.primary_text &&
                   (ad.value.link || isLeadCampaign.value) && ad.value.call_to_action;
        case 4:
            return true;
        default:
            return false;
    }
});

const isLeadCampaign = computed(() => {
    return campaign.value.objective === 'OUTCOME_LEADS';
});

const optimizationGoals = computed(() => {
    const goals = {
        'OUTCOME_AWARENESS': [
            { value: 'REACH', label: 'Qamrov' },
            { value: 'IMPRESSIONS', label: 'Ko\'rishlar' },
        ],
        'OUTCOME_TRAFFIC': [
            { value: 'LINK_CLICKS', label: 'Link kliklar' },
            { value: 'LANDING_PAGE_VIEWS', label: 'Landing sahifa' },
        ],
        'OUTCOME_ENGAGEMENT': [
            { value: 'POST_ENGAGEMENT', label: 'Post engagement' },
            { value: 'THRUPLAY', label: 'Video ko\'rish' },
        ],
        'OUTCOME_LEADS': [
            { value: 'LEAD_GENERATION', label: 'Lid generatsiya' },
            { value: 'CONVERSATIONS', label: 'Suhbatlar' },
        ],
        'OUTCOME_SALES': [
            { value: 'CONVERSIONS', label: 'Konversiyalar' },
        ],
    };
    return goals[campaign.value.objective] || [{ value: 'REACH', label: 'Qamrov' }];
});

const getEnabledPlacementsCount = () => {
    const allPlacements = Object.keys(adSet.value.placements).filter(k => k !== 'automatic');
    return allPlacements.filter(p => adSet.value.placements[p]).length;
};

const isPlatformEnabled = (platform) => {
    const platformPlacements = {
        facebook: ['facebook_feed', 'facebook_marketplace', 'facebook_video_feeds', 'facebook_stories', 'facebook_reels', 'facebook_right_column', 'facebook_search', 'facebook_instream_video'],
        instagram: ['instagram_feed', 'instagram_stories', 'instagram_reels', 'instagram_explore', 'instagram_explore_home', 'instagram_shop', 'instagram_profile_feed'],
        messenger: ['messenger_inbox', 'messenger_stories', 'messenger_sponsored'],
        audience_network: ['audience_network_native', 'audience_network_banner', 'audience_network_interstitial', 'audience_network_rewarded_video'],
    };
    return platformPlacements[platform]?.some(p => adSet.value.placements[p]) || false;
};

const togglePlatformPlacements = (platform) => {
    const platformPlacements = {
        facebook: ['facebook_feed', 'facebook_marketplace', 'facebook_video_feeds', 'facebook_stories', 'facebook_reels', 'facebook_right_column', 'facebook_search', 'facebook_instream_video'],
        instagram: ['instagram_feed', 'instagram_stories', 'instagram_reels', 'instagram_explore', 'instagram_explore_home', 'instagram_shop', 'instagram_profile_feed'],
        messenger: ['messenger_inbox', 'messenger_stories', 'messenger_sponsored'],
        audience_network: ['audience_network_native', 'audience_network_banner', 'audience_network_interstitial', 'audience_network_rewarded_video'],
    };

    const placements = platformPlacements[platform] || [];
    const allEnabled = placements.every(p => adSet.value.placements[p]);

    placements.forEach(p => {
        adSet.value.placements[p] = !allEnabled;
    });
};

// Methods
const loadOptions = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/integrations/meta/api/wizard-options', {
            params: { business_id: props.businessId }
        });
        if (response.data.success) {
            options.value = response.data;
            if (options.value.pages?.length > 0) {
                ad.value.page_id = options.value.pages[0].id;
            }
        }
        // Load lead forms for lead campaigns
        await loadLeadForms();
    } catch (err) {
        console.error('Error loading wizard options:', err);
        error.value = 'Ma\'lumotlarni yuklashda xatolik';
    } finally {
        loading.value = false;
    }
};

const loadLeadForms = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/lead-forms', {
            params: { business_id: props.businessId }
        });
        if (response.data.success) {
            options.value.lead_forms = response.data.data || [];
        }
    } catch (err) {
        console.error('Error loading lead forms:', err);
    }
};

const nextStep = () => {
    if (canProceed.value && currentStep.value < totalSteps) {
        if (currentStep.value === 1 && !adSet.value.name) {
            adSet.value.name = `${campaign.value.name} - AdSet`;
        }
        if (currentStep.value === 2 && !ad.value.name) {
            ad.value.name = `${campaign.value.name} - Ad`;
        }
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const selectObjective = (objective) => {
    campaign.value.objective = objective.value;
    const goals = optimizationGoals.value;
    if (goals.length > 0) {
        adSet.value.optimization_goal = goals[0].value;
    }
};

// Interest search
const searchInterests = debounce(async () => {
    if (interestQuery.value.length < 2) {
        interestResults.value = [];
        return;
    }
    searchingInterests.value = true;
    try {
        const response = await axios.get('/integrations/meta/api/search-interests', {
            params: { q: interestQuery.value, business_id: props.businessId }
        });
        interestResults.value = response.data.data || [];
    } catch (err) {
        console.error('Error searching interests:', err);
    } finally {
        searchingInterests.value = false;
    }
}, 300);

const addInterest = (interest) => {
    if (!selectedInterests.value.find(i => i.id === interest.id)) {
        selectedInterests.value.push(interest);
        updateTargeting();
    }
    interestQuery.value = '';
    interestResults.value = [];
};

const removeInterest = (interest) => {
    selectedInterests.value = selectedInterests.value.filter(i => i.id !== interest.id);
    updateTargeting();
};

// Location search
const searchLocations = debounce(async () => {
    if (locationQuery.value.length < 2) {
        locationResults.value = [];
        return;
    }
    searchingLocations.value = true;
    try {
        const response = await axios.get('/integrations/meta/api/search-locations', {
            params: { q: locationQuery.value, business_id: props.businessId }
        });
        locationResults.value = response.data.data || [];
    } catch (err) {
        console.error('Error searching locations:', err);
    } finally {
        searchingLocations.value = false;
    }
}, 300);

const addLocation = (location) => {
    if (!selectedLocations.value.find(l => l.key === location.key)) {
        selectedLocations.value.push({
            key: location.key,
            name: location.name,
            type: location.type,
            country_code: location.country_code,
        });
        updateLocationTargeting();
        fetchAudienceEstimate();
    }
    locationQuery.value = '';
    locationResults.value = [];
};

const removeLocation = (location) => {
    selectedLocations.value = selectedLocations.value.filter(l => l.key !== location.key);
    updateLocationTargeting();
    fetchAudienceEstimate();
};

const updateLocationTargeting = () => {
    const geoLocations = {
        countries: adSet.value.targeting.geo_locations.countries,
        cities: [],
        regions: [],
    };
    selectedLocations.value.forEach(loc => {
        if (loc.type === 'city') {
            geoLocations.cities.push({ key: loc.key, name: loc.name });
        } else if (loc.type === 'region') {
            geoLocations.regions.push({ key: loc.key, name: loc.name });
        }
    });

    // Add custom locations with radius
    if (customLocations.value.length > 0) {
        geoLocations.custom_locations = customLocations.value.map(loc => ({
            latitude: loc.lat,
            longitude: loc.lng,
            radius: loc.radius,
            distance_unit: 'kilometer',
            name: loc.name,
        }));
    }

    adSet.value.targeting.geo_locations = geoLocations;
};

const updateTargeting = () => {
    if (selectedInterests.value.length > 0) {
        adSet.value.targeting.flexible_spec = [{
            interests: selectedInterests.value.map(i => ({ id: i.id, name: i.name }))
        }];
    } else {
        adSet.value.targeting.flexible_spec = [];
    }
    fetchAudienceEstimate();
};

const toggleCountry = (countryCode) => {
    const countries = adSet.value.targeting.geo_locations.countries;
    const index = countries.indexOf(countryCode);
    if (index > -1) {
        countries.splice(index, 1);
    } else {
        countries.push(countryCode);
    }
    fetchAudienceEstimate();
};

const toggleGender = (gender) => {
    const genders = adSet.value.targeting.genders;
    const index = genders.indexOf(gender);
    if (index > -1 && genders.length > 1) {
        genders.splice(index, 1);
    } else if (index === -1) {
        genders.push(gender);
    }
    fetchAudienceEstimate();
};

// ==================== MAP FUNCTIONS ====================
// Initialize map
const initMap = () => {
    if (map.value || !mapContainer.value) return;

    // Fix Leaflet default icon issue
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    });

    // Initialize map centered on Central Asia
    map.value = L.map(mapContainer.value, {
        center: [41.377491, 64.585262],
        zoom: 4,
        zoomControl: true,
        attributionControl: false,
    });

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map.value);

    // Add click handler
    map.value.on('click', handleMapClick);

    // Update markers and circles
    updateMapMarkers();
    updateMapCircles();
};

// Update map markers for selected countries
const updateMapMarkers = () => {
    if (!map.value) return;

    // Clear existing markers
    mapMarkers.value.forEach(marker => marker.remove());
    mapMarkers.value = [];

    // Add markers for selected countries
    adSet.value.targeting.geo_locations.countries.forEach(countryCode => {
        const coords = countryCoordinates[countryCode];
        if (coords) {
            const marker = L.marker([coords.lat, coords.lng])
                .addTo(map.value)
                .bindPopup(coords.name);
            mapMarkers.value.push(marker);
        }
    });

    // Fit bounds if we have markers
    if (mapMarkers.value.length > 0) {
        const bounds = mapMarkers.value.map(m => m.getLatLng());
        if (bounds.length === 1) {
            map.value.setView(bounds[0], 5);
        } else {
            map.value.fitBounds(bounds, { padding: [50, 50] });
        }
    }
};

// Reverse geocode to get location name
const reverseGeocode = async (lat, lng) => {
    const cacheKey = `${lat.toFixed(4)}_${lng.toFixed(4)}`;

    if (geocodeCache.value[cacheKey]) {
        return geocodeCache.value[cacheKey];
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10&addressdetails=1`
        );
        const data = await response.json();

        const result = {
            name: data.address?.city || data.address?.town || data.address?.village || data.address?.municipality || data.address?.county || 'Noma\'lum joy',
            fullName: data.display_name || '',
            country: data.address?.country || '',
            countryCode: data.address?.country_code?.toUpperCase() || '',
        };

        geocodeCache.value[cacheKey] = result;
        return result;
    } catch (err) {
        console.error('Reverse geocoding error:', err);
        return {
            name: `${lat.toFixed(4)}, ${lng.toFixed(4)}`,
            fullName: '',
            country: '',
            countryCode: '',
        };
    }
};

// Add custom location from map click
const addCustomLocation = async (lat, lng, isExcluded = false) => {
    isAddingLocation.value = true;

    try {
        const geoData = await reverseGeocode(lat, lng);
        const location = {
            id: `custom_${Date.now()}`,
            lat: lat,
            lng: lng,
            name: geoData.name,
            fullName: geoData.fullName,
            country: geoData.country,
            countryCode: geoData.countryCode,
            radius: defaultRadius.value,
            isExcluded: isExcluded,
        };

        if (isExcluded) {
            excludedLocations.value.push(location);
        } else {
            customLocations.value.push(location);
        }

        updateMapCircles();
        updateLocationTargeting();
        fetchAudienceEstimate();
    } finally {
        isAddingLocation.value = false;
    }
};

// Remove custom location
const removeCustomLocation = (locationId, isExcluded = false) => {
    if (isExcluded) {
        excludedLocations.value = excludedLocations.value.filter(l => l.id !== locationId);
    } else {
        customLocations.value = customLocations.value.filter(l => l.id !== locationId);
    }
    updateMapCircles();
    updateLocationTargeting();
    fetchAudienceEstimate();
};

// Update location radius
const updateLocationRadius = (locationId, newRadius, isExcluded = false) => {
    const locations = isExcluded ? excludedLocations.value : customLocations.value;
    const location = locations.find(l => l.id === locationId);
    if (location) {
        location.radius = newRadius;
        updateMapCircles();
        updateLocationTargeting();
        fetchAudienceEstimate();
    }
};

// Update map circles for custom locations
const updateMapCircles = () => {
    if (!map.value) return;

    // Clear existing circles
    mapCircles.value.forEach(circle => circle.remove());
    mapCircles.value = [];

    // Add circles for custom included locations
    customLocations.value.forEach(loc => {
        const circle = L.circle([loc.lat, loc.lng], {
            radius: loc.radius * 1000, // Convert km to meters
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.2,
            weight: 2,
        }).addTo(map.value);

        circle.bindPopup(`
            <div class="p-2">
                <strong>${loc.name}</strong><br>
                <small>${loc.country}</small><br>
                <span class="text-blue-600">Radius: ${loc.radius} km</span>
            </div>
        `);
        mapCircles.value.push(circle);
    });

    // Add circles for excluded locations
    excludedLocations.value.forEach(loc => {
        const circle = L.circle([loc.lat, loc.lng], {
            radius: loc.radius * 1000,
            color: '#ef4444',
            fillColor: '#ef4444',
            fillOpacity: 0.2,
            weight: 2,
            dashArray: '5, 10',
        }).addTo(map.value);

        circle.bindPopup(`
            <div class="p-2">
                <strong>${loc.name}</strong> (Istisno)<br>
                <small>${loc.country}</small><br>
                <span class="text-red-600">Radius: ${loc.radius} km</span>
            </div>
        `);
        mapCircles.value.push(circle);
    });

    // Fit bounds to show all circles
    if (customLocations.value.length > 0 || excludedLocations.value.length > 0) {
        const allLocations = [...customLocations.value, ...excludedLocations.value];
        const bounds = allLocations.map(loc => [loc.lat, loc.lng]);
        if (bounds.length === 1) {
            map.value.setView(bounds[0], 10);
        } else if (bounds.length > 1) {
            map.value.fitBounds(bounds, { padding: [50, 50] });
        }
    }
};

// Handle map click
const handleMapClick = (e) => {
    if (mapClickMode.value) {
        const isExcluded = mapClickMode.value === 'exclude';
        addCustomLocation(e.latlng.lat, e.latlng.lng, isExcluded);
    }
};

// Set map click mode
const setMapClickMode = (mode) => {
    mapClickMode.value = mapClickMode.value === mode ? null : mode;
};

// Toggle map view
const toggleMapView = () => {
    showMap.value = !showMap.value;
    if (showMap.value) {
        nextTick(() => {
            initMap();
        });
    }
};

// Destroy map
const destroyMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    mapMarkers.value = [];
    mapCircles.value = [];
};

// Audience estimate
const fetchAudienceEstimate = debounce(async () => {
    audienceEstimate.value.loading = true;
    try {
        const response = await axios.post('/integrations/meta/api/reach-estimate', {
            business_id: props.businessId,
            targeting: adSet.value.targeting,
            optimization_goal: adSet.value.optimization_goal,
        });
        if (response.data.success && response.data.data) {
            audienceEstimate.value.users_lower_bound = response.data.data.users_lower_bound || 0;
            audienceEstimate.value.users_upper_bound = response.data.data.users_upper_bound || 0;
        }
    } catch (err) {
        console.error('Error fetching audience estimate:', err);
    } finally {
        audienceEstimate.value.loading = false;
    }
}, 500);

// Image upload
const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploadingImage.value = true;
    const formData = new FormData();
    formData.append('image', file);
    formData.append('business_id', props.businessId);

    try {
        const response = await axios.post('/integrations/meta/api/upload-image', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (response.data.success) {
            ad.value.image_hash = response.data.image_hash;
            ad.value.image_preview = URL.createObjectURL(file);
        }
    } catch (err) {
        console.error('Error uploading image:', err);
        error.value = 'Rasmni yuklashda xatolik';
    } finally {
        uploadingImage.value = false;
    }
};

// Submit
const submitAd = async () => {
    submitting.value = true;
    error.value = null;

    try {
        const payload = {
            business_id: props.businessId,
            campaign: campaign.value,
            adset: {
                ...adSet.value,
                targeting: {
                    ...adSet.value.targeting,
                    flexible_spec: selectedInterests.value.length > 0 ? [{
                        interests: selectedInterests.value.map(i => ({ id: i.id, name: i.name }))
                    }] : [],
                },
            },
            ad: {
                ...ad.value,
                lead_form_id: isLeadCampaign.value ? ad.value.lead_form_id : null,
            },
        };

        const response = await axios.post('/integrations/meta/api/campaign/create', payload);

        if (response.data.success) {
            success.value = 'Reklama muvaffaqiyatli yaratildi!';
            setTimeout(() => {
                router.visit('/business/target-analysis');
            }, 2000);
        } else {
            error.value = response.data.message || 'Xatolik yuz berdi';
        }
    } catch (err) {
        console.error('Error creating ad:', err);
        error.value = err.response?.data?.message || 'Reklama yaratishda xatolik';
    } finally {
        submitting.value = false;
    }
};

const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);

const getObjectiveLabel = (objective) => {
    const labels = {
        'OUTCOME_AWARENESS': 'Xabardorlik',
        'OUTCOME_TRAFFIC': 'Trafik',
        'OUTCOME_ENGAGEMENT': 'Engagement',
        'OUTCOME_LEADS': 'Lidlar',
        'OUTCOME_SALES': 'Sotuvlar',
    };
    return labels[objective] || objective;
};

// Watch for step change to init map
watch(currentStep, (newStep) => {
    if (newStep === 2) {
        nextTick(() => {
            if (showMap.value && !map.value) {
                initMap();
            }
        });
    }
});

// Watch custom locations to update circles
watch([customLocations, excludedLocations], () => {
    updateMapCircles();
}, { deep: true });

onMounted(() => {
    loadOptions();
});

onUnmounted(() => {
    destroyMap();
});
</script>

<template>
    <Head title="Yangi reklama yaratish" />

    <BusinessLayout title="Yangi reklama yaratish">
        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between py-4">
                        <div class="flex items-center gap-4">
                            <Link href="/business/target-analysis" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">Yangi reklama yaratish</h1>
                                <p class="text-sm text-gray-500">Meta Ads Manager</p>
                            </div>
                        </div>

                        <!-- Step indicator -->
                        <div class="hidden md:flex items-center gap-2">
                            <template v-for="(name, index) in stepNames" :key="index">
                                <div class="flex items-center">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all',
                                        currentStep > index + 1 ? 'bg-green-500 text-white' :
                                        currentStep === index + 1 ? 'bg-blue-600 text-white' :
                                        'bg-gray-200 text-gray-600'
                                    ]">
                                        <svg v-if="currentStep > index + 1" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span v-else>{{ index + 1 }}</span>
                                    </div>
                                    <span :class="[
                                        'ml-2 text-sm font-medium',
                                        currentStep === index + 1 ? 'text-blue-600' : 'text-gray-500'
                                    ]">{{ name }}</span>
                                </div>
                                <div v-if="index < stepNames.length - 1" class="w-12 h-0.5 mx-2" :class="currentStep > index + 1 ? 'bg-green-500' : 'bg-gray-200'"></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <div v-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-red-700">{{ error }}</span>
                </div>
            </div>

            <div v-if="success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-green-700">{{ success }}</span>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-24">
                    <div class="flex flex-col items-center gap-4">
                        <div class="animate-spin w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
                        <p class="text-gray-500">Ma'lumotlar yuklanmoqda...</p>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Form Area -->
                    <div class="lg:col-span-2">
                        <!-- Step 1: Campaign -->
                        <div v-if="currentStep === 1" class="space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Kampaniya ma'lumotlari</h2>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kampaniya nomi *</label>
                                        <input v-model="campaign.name" type="text" placeholder="Masalan: Yangi yil aksiyasi"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Kampaniya maqsadi *</label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <button v-for="obj in options.objectives" :key="obj.value"
                                                @click="selectObjective(obj)"
                                                :class="[
                                                    'p-4 border-2 rounded-xl text-left transition-all',
                                                    campaign.objective === obj.value
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                ]">
                                                <div class="flex items-start gap-3">
                                                    <div :class="[
                                                        'w-10 h-10 rounded-lg flex items-center justify-center',
                                                        campaign.objective === obj.value ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600'
                                                    ]">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                :d="obj.value === 'OUTCOME_AWARENESS' ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' :
                                                                   obj.value === 'OUTCOME_TRAFFIC' ? 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122' :
                                                                   obj.value === 'OUTCOME_ENGAGEMENT' ? 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' :
                                                                   obj.value === 'OUTCOME_LEADS' ? 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' :
                                                                   'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="font-medium text-gray-900">{{ obj.label }}</p>
                                                        <p class="text-sm text-gray-500 mt-1">{{ obj.description }}</p>
                                                    </div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Audience & Budget -->
                        <div v-else-if="currentStep === 2" class="space-y-6">
                            <!-- AdSet Name & Budget -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ad Set sozlamalari</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Ad Set nomi *</label>
                                        <input v-model="adSet.name" type="text" placeholder="Masalan: Toshkent auditoriyasi"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kunlik byudjet ({{ currency }}) *</label>
                                        <input v-model.number="adSet.daily_budget" type="number" min="1" step="0.01"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Optimizatsiya maqsadi *</label>
                                    <select v-model="adSet.optimization_goal"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option v-for="goal in optimizationGoals" :key="goal.value" :value="goal.value">
                                            {{ goal.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Targeting -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Auditoriya sozlamalari</h2>

                                <!-- Countries -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Mamlakatlar</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button v-for="country in options.countries" :key="country.code"
                                            @click="toggleCountry(country.code)"
                                            :class="[
                                                'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                                                adSet.targeting.geo_locations.countries.includes(country.code)
                                                    ? 'bg-blue-500 text-white'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            ]">
                                            {{ country.name }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Location Search -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Shahar/hudud qidirish</label>
                                    <div class="relative">
                                        <input v-model="locationQuery" @input="searchLocations" type="text"
                                            placeholder="Shahar yoki hudud nomini kiriting..."
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">

                                        <div v-if="locationResults.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <button v-for="loc in locationResults" :key="loc.key"
                                                @click="addLocation(loc)"
                                                class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3">
                                                <span class="text-gray-900">{{ loc.name }}</span>
                                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ loc.type }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="selectedLocations.length > 0" class="flex flex-wrap gap-2 mt-3">
                                        <span v-for="loc in selectedLocations" :key="loc.key"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            {{ loc.name }}
                                            <button @click="removeLocation(loc)" class="hover:text-blue-600">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                </div>

                                <!-- Map Section - Like Facebook Ads Manager -->
                                <div class="mb-6 space-y-3">
                                    <!-- Map Header with Controls -->
                                    <div class="flex items-center justify-between">
                                        <label class="block text-sm font-medium text-gray-700">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                            </svg>
                                            Xaritadan joy tanlash
                                        </label>
                                        <button @click="toggleMapView" class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                            <svg v-if="showMap" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            {{ showMap ? 'Yashirish' : 'Ko\'rsatish' }}
                                        </button>
                                    </div>

                                    <div v-if="showMap" class="space-y-3">
                                        <!-- Map Mode Buttons -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <button @click="setMapClickMode('add')"
                                                :class="[
                                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition border-2',
                                                    mapClickMode === 'add'
                                                        ? 'bg-blue-50 border-blue-500 text-blue-700'
                                                        : 'bg-white border-gray-200 text-gray-700 hover:border-blue-300'
                                                ]">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                Qo'shish
                                            </button>
                                            <button @click="setMapClickMode('exclude')"
                                                :class="[
                                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition border-2',
                                                    mapClickMode === 'exclude'
                                                        ? 'bg-red-50 border-red-500 text-red-700'
                                                        : 'bg-white border-gray-200 text-gray-700 hover:border-red-300'
                                                ]">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                Istisno
                                            </button>
                                            <span class="text-xs text-gray-500 ml-2">
                                                <svg v-if="mapClickMode" class="w-4 h-4 inline animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2z" />
                                                </svg>
                                                {{ mapClickMode === 'add' ? 'Xaritaga bosib joy qo\'shing' : mapClickMode === 'exclude' ? 'Xaritaga bosib istisno qiling' : '' }}
                                            </span>
                                            <div v-if="isAddingLocation" class="ml-auto">
                                                <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Default Radius Selector -->
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm text-gray-600">Standart radius:</span>
                                            <select v-model="defaultRadius" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-900">
                                                <option v-for="r in radiusOptions" :key="r" :value="r">{{ r }} km</option>
                                            </select>
                                        </div>

                                        <!-- Map Container -->
                                        <div class="relative rounded-xl overflow-hidden border border-gray-200" :class="{ 'ring-2 ring-blue-500': mapClickMode === 'add', 'ring-2 ring-red-500': mapClickMode === 'exclude' }">
                                            <div ref="mapContainer" class="w-full h-80" :class="{ 'cursor-crosshair': mapClickMode }"></div>

                                            <!-- Map Legend -->
                                            <div class="absolute bottom-2 left-2 bg-white/95 backdrop-blur-sm rounded-lg px-3 py-2 text-xs shadow-lg">
                                                <div class="flex flex-col gap-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                                        <span class="text-gray-700">Kiritilgan hudud</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                        <span class="text-gray-700">Istisno hudud</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Location Counter -->
                                            <div v-if="customLocations.length > 0 || excludedLocations.length > 0" class="absolute top-2 right-2 flex gap-2">
                                                <div v-if="customLocations.length > 0" class="bg-blue-600 text-white rounded-full px-3 py-1 text-xs font-medium shadow-lg">
                                                    +{{ customLocations.length }}
                                                </div>
                                                <div v-if="excludedLocations.length > 0" class="bg-red-500 text-white rounded-full px-3 py-1 text-xs font-medium shadow-lg">
                                                    -{{ excludedLocations.length }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Custom Included Locations List -->
                                        <div v-if="customLocations.length > 0" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                <span class="text-blue-600">+</span> Kiritilgan hududlar
                                            </label>
                                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                                <div v-for="loc in customLocations" :key="loc.id"
                                                    class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                                    <div class="flex items-center gap-2 flex-1">
                                                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            </svg>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="font-medium text-gray-900 text-sm truncate">{{ loc.name }}</div>
                                                            <div class="text-xs text-gray-500 truncate">{{ loc.country }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <select :value="loc.radius" @change="updateLocationRadius(loc.id, parseInt($event.target.value), false)"
                                                            class="px-2 py-1 text-xs border border-blue-300 rounded-lg bg-white text-gray-900">
                                                            <option v-for="r in radiusOptions" :key="r" :value="r">{{ r }} km</option>
                                                        </select>
                                                        <button @click="removeCustomLocation(loc.id, false)" class="p-1 text-blue-600 hover:text-red-600 transition">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Custom Excluded Locations List -->
                                        <div v-if="excludedLocations.length > 0" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                <span class="text-red-600">-</span> Istisno qilingan hududlar
                                            </label>
                                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                                <div v-for="loc in excludedLocations" :key="loc.id"
                                                    class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                                                    <div class="flex items-center gap-2 flex-1">
                                                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                            </svg>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="font-medium text-gray-900 text-sm truncate">{{ loc.name }}</div>
                                                            <div class="text-xs text-gray-500 truncate">{{ loc.country }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <select :value="loc.radius" @change="updateLocationRadius(loc.id, parseInt($event.target.value), true)"
                                                            class="px-2 py-1 text-xs border border-red-300 rounded-lg bg-white text-gray-900">
                                                            <option v-for="r in radiusOptions" :key="r" :value="r">{{ r }} km</option>
                                                        </select>
                                                        <button @click="removeCustomLocation(loc.id, true)" class="p-1 text-red-600 hover:text-red-700 transition">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Age & Gender -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Yosh oralig'i</label>
                                        <div class="flex items-center gap-3">
                                            <input v-model.number="adSet.targeting.age_min" type="number" min="13" max="65"
                                                class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center">
                                            <span class="text-gray-500">dan</span>
                                            <input v-model.number="adSet.targeting.age_max" type="number" min="13" max="65"
                                                class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center">
                                            <span class="text-gray-500">gacha</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Jins</label>
                                        <div class="flex gap-3">
                                            <button @click="toggleGender(1)"
                                                :class="[
                                                    'flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                                                    adSet.targeting.genders.includes(1)
                                                        ? 'bg-blue-500 text-white'
                                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                ]">
                                                Erkak
                                            </button>
                                            <button @click="toggleGender(2)"
                                                :class="[
                                                    'flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                                                    adSet.targeting.genders.includes(2)
                                                        ? 'bg-pink-500 text-white'
                                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                ]">
                                                Ayol
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Interest Search -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Qiziqishlar</label>
                                    <div class="relative">
                                        <input v-model="interestQuery" @input="searchInterests" type="text"
                                            placeholder="Qiziqish nomini kiriting..."
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">

                                        <div v-if="interestResults.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <button v-for="interest in interestResults" :key="interest.id"
                                                @click="addInterest(interest)"
                                                class="w-full px-4 py-3 text-left hover:bg-gray-50">
                                                <span class="text-gray-900">{{ interest.name }}</span>
                                                <span v-if="interest.audience_size" class="text-xs text-gray-500 ml-2">
                                                    {{ formatNumber(interest.audience_size) }} kishi
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="selectedInterests.length > 0" class="flex flex-wrap gap-2 mt-3">
                                        <span v-for="interest in selectedInterests" :key="interest.id"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-100 text-purple-800 rounded-full text-sm">
                                            {{ interest.name }}
                                            <button @click="removeInterest(interest)" class="hover:text-purple-600">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Placements -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-semibold text-gray-900">Joylashuvlar (Placements)</h2>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" v-model="adSet.placements.automatic"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-sm font-medium text-gray-700">Advantage+ (Avtomatik)</span>
                                    </label>
                                </div>

                                <div v-if="!adSet.placements.automatic" class="space-y-4">
                                    <!-- Facebook -->
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900">Facebook</span>
                                            </div>
                                            <button @click="togglePlatformPlacements('facebook')"
                                                class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                {{ isPlatformEnabled('facebook') ? 'Barchasini o\'chirish' : 'Barchasini yoqish' }}
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_feed" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Feed</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_stories" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Stories</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_reels" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Reels</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_marketplace" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Marketplace</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_video_feeds" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Video feeds</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_right_column" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Right column</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_search" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">Search</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_instream_video" class="w-4 h-4 text-blue-600 rounded">
                                                <span class="text-sm text-gray-700">In-stream video</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Instagram -->
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900">Instagram</span>
                                            </div>
                                            <button @click="togglePlatformPlacements('instagram')"
                                                class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                {{ isPlatformEnabled('instagram') ? 'Barchasini o\'chirish' : 'Barchasini yoqish' }}
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_feed" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Feed</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_stories" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Stories</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_reels" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Reels</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_explore" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Explore</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_explore_home" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Explore Home</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_shop" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Shop</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_profile_feed" class="w-4 h-4 text-pink-600 rounded">
                                                <span class="text-sm text-gray-700">Profile feed</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Messenger -->
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.301 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.259L19.752 8l-6.561 6.963z"/>
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900">Messenger</span>
                                            </div>
                                            <button @click="togglePlatformPlacements('messenger')"
                                                class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                {{ isPlatformEnabled('messenger') ? 'Barchasini o\'chirish' : 'Barchasini yoqish' }}
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.messenger_inbox" class="w-4 h-4 text-purple-600 rounded">
                                                <span class="text-sm text-gray-700">Inbox</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.messenger_stories" class="w-4 h-4 text-purple-600 rounded">
                                                <span class="text-sm text-gray-700">Stories</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.messenger_sponsored" class="w-4 h-4 text-purple-600 rounded">
                                                <span class="text-sm text-gray-700">Sponsored messages</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Audience Network -->
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gray-700 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900">Audience Network</span>
                                            </div>
                                            <button @click="togglePlatformPlacements('audience_network')"
                                                class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                {{ isPlatformEnabled('audience_network') ? 'Barchasini o\'chirish' : 'Barchasini yoqish' }}
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.audience_network_native" class="w-4 h-4 text-gray-600 rounded">
                                                <span class="text-sm text-gray-700">Native</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.audience_network_banner" class="w-4 h-4 text-gray-600 rounded">
                                                <span class="text-sm text-gray-700">Banner</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.audience_network_interstitial" class="w-4 h-4 text-gray-600 rounded">
                                                <span class="text-sm text-gray-700">Interstitial</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.audience_network_rewarded_video" class="w-4 h-4 text-gray-600 rounded">
                                                <span class="text-sm text-gray-700">Rewarded video</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <p v-else class="text-sm text-gray-500 mt-2">
                                    Meta sizning reklamangizni eng yaxshi natija beradigan joylarga avtomatik joylashtiradi.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3: Creative -->
                        <div v-else-if="currentStep === 3" class="space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Kreativ sozlamalari</h2>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Reklama nomi *</label>
                                        <input v-model="ad.name" type="text" placeholder="Reklama nomi"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Facebook sahifa *</label>
                                        <select v-model="ad.page_id"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">Sahifa tanlang</option>
                                            <option v-for="page in options.pages" :key="page.id" :value="page.id">
                                                {{ page.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Lead Form Selection (for Lead Campaigns) -->
                                    <div v-if="isLeadCampaign" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <label class="block text-sm font-medium text-green-800 mb-2">
                                            Lead forma tanlang *
                                        </label>
                                        <select v-model="ad.lead_form_id"
                                            class="w-full px-4 py-3 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 bg-white">
                                            <option value="">Forma tanlang</option>
                                            <option v-for="form in options.lead_forms" :key="form.id" :value="form.id">
                                                {{ form.name }}
                                            </option>
                                        </select>
                                        <p class="text-sm text-green-600 mt-2">
                                            Lead kampaniyasi uchun forma tanlanishi kerak. Forma Meta Business Suite'da yaratilishi kerak.
                                        </p>
                                    </div>

                                    <!-- Image Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Rasm yuklash</label>
                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                                            <input type="file" @change="handleImageUpload" accept="image/*" class="hidden" id="image-upload">
                                            <label for="image-upload" class="cursor-pointer">
                                                <div v-if="ad.image_preview" class="mb-4">
                                                    <img :src="ad.image_preview" alt="Preview" class="max-h-48 mx-auto rounded-lg">
                                                </div>
                                                <div v-else class="text-gray-500">
                                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <p class="text-sm">Rasm yuklash uchun bosing</p>
                                                    <p class="text-xs text-gray-400 mt-1">PNG, JPG (1200x628 tavsiya etiladi)</p>
                                                </div>
                                            </label>
                                            <div v-if="uploadingImage" class="mt-3">
                                                <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Asosiy matn *
                                            <span class="text-gray-400 font-normal">({{ ad.primary_text.length }}/125)</span>
                                        </label>
                                        <textarea v-model="ad.primary_text" rows="3" maxlength="125"
                                            placeholder="Reklamangizning asosiy matni..."
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Sarlavha
                                                <span class="text-gray-400 font-normal">({{ ad.headline.length }}/40)</span>
                                            </label>
                                            <input v-model="ad.headline" type="text" maxlength="40"
                                                placeholder="Qisqa sarlavha"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Tavsif
                                                <span class="text-gray-400 font-normal">({{ ad.description.length }}/30)</span>
                                            </label>
                                            <input v-model="ad.description" type="text" maxlength="30"
                                                placeholder="Qo'shimcha tavsif"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <div v-if="!isLeadCampaign">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Havola (URL) *</label>
                                        <input v-model="ad.link" type="url" placeholder="https://example.com"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Harakatga chaqiruv tugmasi</label>
                                        <select v-model="ad.call_to_action"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option v-for="cta in options.call_to_actions" :key="cta.value" :value="cta.value">
                                                {{ cta.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Review -->
                        <div v-else-if="currentStep === 4" class="space-y-6">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-6 text-white">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h2 class="text-xl font-bold">Reklamani tekshirish</h2>
                                </div>
                                <p class="text-green-100">Barcha ma'lumotlarni tekshiring va reklamani yarating</p>
                            </div>

                            <!-- Campaign Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-semibold text-gray-900 mb-4">Kampaniya</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Nomi</p>
                                        <p class="font-medium text-gray-900">{{ campaign.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Maqsad</p>
                                        <p class="font-medium text-gray-900">{{ getObjectiveLabel(campaign.objective) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- AdSet Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-semibold text-gray-900 mb-4">Auditoriya va Byudjet</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Ad Set nomi</p>
                                        <p class="font-medium text-gray-900">{{ adSet.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Kunlik byudjet</p>
                                        <p class="font-medium text-gray-900">{{ adSet.daily_budget }} {{ currency }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Yosh oralig'i</p>
                                        <p class="font-medium text-gray-900">{{ adSet.targeting.age_min }} - {{ adSet.targeting.age_max }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Joylashuvlar</p>
                                        <p class="font-medium text-gray-900">
                                            {{ adSet.placements.automatic ? 'Avtomatik' : getEnabledPlacementsCount() + ' ta tanlangan' }}
                                        </p>
                                    </div>
                                    <div v-if="customLocations.length > 0">
                                        <p class="text-sm text-gray-500">Maxsus joylar</p>
                                        <p class="font-medium text-blue-600">+{{ customLocations.length }} joy</p>
                                    </div>
                                    <div v-if="excludedLocations.length > 0">
                                        <p class="text-sm text-gray-500">Istisno joylar</p>
                                        <p class="font-medium text-red-600">-{{ excludedLocations.length }} joy</p>
                                    </div>
                                    <div v-if="selectedInterests.length">
                                        <p class="text-sm text-gray-500">Qiziqishlar</p>
                                        <p class="font-medium text-gray-900">{{ selectedInterests.length }} ta</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Ad Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-semibold text-gray-900 mb-4">Kreativ</h3>
                                <div class="flex gap-6">
                                    <div v-if="ad.image_preview" class="flex-shrink-0">
                                        <img :src="ad.image_preview" alt="Ad preview" class="w-32 h-32 object-cover rounded-lg">
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <div>
                                            <p class="text-sm text-gray-500">Reklama nomi</p>
                                            <p class="font-medium text-gray-900">{{ ad.name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Asosiy matn</p>
                                            <p class="text-gray-700">{{ ad.primary_text }}</p>
                                        </div>
                                        <div v-if="ad.headline">
                                            <p class="text-sm text-gray-500">Sarlavha</p>
                                            <p class="font-medium text-gray-900">{{ ad.headline }}</p>
                                        </div>
                                        <div v-if="isLeadCampaign && ad.lead_form_id">
                                            <p class="text-sm text-gray-500">Lead forma</p>
                                            <p class="font-medium text-green-600">
                                                {{ options.lead_forms.find(f => f.id === ad.lead_form_id)?.name || 'Tanlangan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Warning -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-yellow-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-yellow-800">Diqqat</p>
                                        <p class="text-sm text-yellow-700">Reklama PAUSED holatda yaratiladi. Uni faollashtirish uchun Meta Ads Manager'da statusni o'zgartiring.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <!-- Audience Estimate -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
                            <h3 class="font-semibold text-gray-900 mb-4">Taxminiy auditoriya</h3>

                            <div class="text-center py-6">
                                <div v-if="audienceEstimate.loading" class="animate-pulse">
                                    <div class="h-8 bg-gray-200 rounded w-32 mx-auto mb-2"></div>
                                    <div class="h-4 bg-gray-200 rounded w-24 mx-auto"></div>
                                </div>
                                <div v-else>
                                    <p class="text-3xl font-bold text-gray-900">
                                        {{ formatNumber(audienceEstimate.users_lower_bound) }} - {{ formatNumber(audienceEstimate.users_upper_bound) }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">potensial qamrov</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Kunlik byudjet</span>
                                        <span class="font-medium text-gray-900">{{ adSet.daily_budget }} {{ currency }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Haftalik sarflama</span>
                                        <span class="font-medium text-gray-900">{{ (adSet.daily_budget * 7).toFixed(2) }} {{ currency }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Oylik sarflama</span>
                                        <span class="font-medium text-gray-900">{{ (adSet.daily_budget * 30).toFixed(2) }} {{ currency }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-4 z-20">
                    <div class="max-w-7xl mx-auto flex items-center justify-between">
                        <button v-if="currentStep > 1" @click="prevStep"
                            class="px-6 py-2.5 text-gray-700 font-medium hover:text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Ortga
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-3">
                            <Link href="/business/target-analysis"
                                class="px-6 py-2.5 text-gray-600 font-medium hover:text-gray-900">
                                Bekor qilish
                            </Link>

                            <button v-if="currentStep < totalSteps" @click="nextStep" :disabled="!canProceed"
                                class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center gap-2">
                                Keyingisi
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <button v-else @click="submitAd" :disabled="submitting"
                                class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium flex items-center gap-2">
                                <svg v-if="submitting" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ submitting ? 'Yaratilmoqda...' : 'Reklamani yaratish' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<style scoped>
/* Cursor crosshair for map click mode */
.cursor-crosshair :deep(.leaflet-container) {
    cursor: crosshair !important;
}
</style>
