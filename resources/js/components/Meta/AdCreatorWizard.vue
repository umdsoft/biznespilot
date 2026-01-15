<script setup>
import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    businessId: { type: [String, Number], required: true },
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'created', 'error']);

// Wizard state
const currentStep = ref(1);
const totalSteps = 4;
const loading = ref(false);
const submitting = ref(false);

// Options loaded from API
const options = ref({
    objectives: [],
    countries: [],
    call_to_actions: [],
    pages: [],
    ad_account: null,
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
        genders: [1, 2], // 1=male, 2=female
        flexible_spec: [], // For OR targeting
        exclusions: {}, // Excluded audiences
    },
    placements: {
        automatic: true,
        // Facebook placements
        facebook_feed: true,
        facebook_marketplace: true,
        facebook_video_feeds: true,
        facebook_stories: true,
        facebook_reels: true,
        facebook_right_column: false,
        facebook_search: false,
        // Instagram placements
        instagram_feed: true,
        instagram_stories: true,
        instagram_reels: true,
        instagram_explore: true,
        instagram_shop: false,
        // Audience Network
        audience_network: false,
        // Messenger
        messenger_inbox: false,
        messenger_stories: false,
    },
    start_time: '',
    end_time: '',
    bid_strategy: 'LOWEST_COST_WITHOUT_CAP',
});

// Audience estimate
const audienceEstimate = ref({
    users_lower_bound: 0,
    users_upper_bound: 0,
    loading: false,
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
    format: 'single_image', // single_image, carousel, video
});

// Preview format
const previewFormat = ref('facebook_feed'); // facebook_feed, instagram_feed, instagram_story, facebook_story

// Available preview formats
const previewFormats = [
    { value: 'facebook_feed', label: 'Facebook Feed', icon: 'facebook' },
    { value: 'instagram_feed', label: 'Instagram Feed', icon: 'instagram' },
    { value: 'instagram_story', label: 'Instagram Story', icon: 'instagram' },
    { value: 'facebook_story', label: 'Facebook Story', icon: 'facebook' },
];

// Interest search
const interestQuery = ref('');
const interestResults = ref([]);
const selectedInterests = ref([]);
const searchingInterests = ref(false);
const interestCategories = ref([
    { id: 'business', name: 'Biznes va sanoat', icon: 'briefcase' },
    { id: 'entertainment', name: 'Ko\'ngilochar', icon: 'film' },
    { id: 'family', name: 'Oila va munosabatlar', icon: 'heart' },
    { id: 'fitness', name: 'Fitnes va salomatlik', icon: 'activity' },
    { id: 'food', name: 'Ovqat va ichimlik', icon: 'coffee' },
    { id: 'hobbies', name: 'Xobbi va qiziqishlar', icon: 'star' },
    { id: 'shopping', name: 'Xarid qilish', icon: 'shopping-bag' },
    { id: 'sports', name: 'Sport', icon: 'trophy' },
    { id: 'technology', name: 'Texnologiya', icon: 'cpu' },
    { id: 'travel', name: 'Sayohat', icon: 'map' },
]);
const showInterestBrowser = ref(false);
const selectedInterestCategory = ref(null);
const categoryInterests = ref([]);
const loadingCategoryInterests = ref(false);

// Behavior search
const behaviorQuery = ref('');
const behaviorResults = ref([]);
const selectedBehaviors = ref([]);
const searchingBehaviors = ref(false);

// Location search
const locationQuery = ref('');
const locationResults = ref([]);
const selectedLocations = ref([]);
const searchingLocations = ref(false);

// Demographics
const demographics = ref({
    education_statuses: [], // 1=HIGH_SCHOOL, 2=UNDERGRAD, 3=ALUM, etc.
    relationship_statuses: [], // 1=single, 2=in_relationship, 3=married, etc.
    life_events: [],
});

const educationOptions = [
    { value: 1, label: 'O\'rta maktab' },
    { value: 2, label: 'Bakalavr (o\'qiyotgan)' },
    { value: 3, label: 'Bakalavr (tugatgan)' },
    { value: 4, label: 'Magistr' },
    { value: 5, label: 'Doktorantura' },
];

const relationshipOptions = [
    { value: 1, label: 'Yolg\'iz' },
    { value: 2, label: 'Munosabatda' },
    { value: 3, label: 'Turmush qurgan' },
    { value: 4, label: 'Unashtirilgan' },
];

// Active targeting section
const activeTargetingSection = ref('location'); // location, demographics, interests, behaviors

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
const defaultRadius = ref(25); // Default radius in km
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

// Computed
const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1: // Campaign
            return campaign.value.name && campaign.value.objective;
        case 2: // AdSet
            return adSet.value.name && adSet.value.daily_budget >= 1 && adSet.value.optimization_goal;
        case 3: // Ad
            return ad.value.name && ad.value.page_id && ad.value.primary_text && ad.value.link && ad.value.call_to_action;
        case 4: // Review
            return true;
        default:
            return false;
    }
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

// Get objective icon SVG path
const getObjectiveIcon = (iconName) => {
    const icons = {
        'eye': 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'cursor-click': 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122',
        'heart': 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        'device-mobile': 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    };
    return icons[iconName] || icons['eye'];
};

// Get icon color based on selection
const getIconBgClass = (objValue) => {
    const colors = {
        'OUTCOME_AWARENESS': 'bg-purple-500',
        'OUTCOME_TRAFFIC': 'bg-blue-500',
        'OUTCOME_ENGAGEMENT': 'bg-pink-500',
        'OUTCOME_LEADS': 'bg-green-500',
        'OUTCOME_SALES': 'bg-orange-500',
        'OUTCOME_APP_PROMOTION': 'bg-indigo-500',
    };
    return colors[objValue] || 'bg-blue-500';
};

// Map objective value to icon name
const getObjectiveIconName = (objValue) => {
    const iconMap = {
        'OUTCOME_AWARENESS': 'eye',
        'OUTCOME_TRAFFIC': 'cursor-click',
        'OUTCOME_ENGAGEMENT': 'heart',
        'OUTCOME_LEADS': 'user-plus',
        'OUTCOME_SALES': 'shopping-cart',
        'OUTCOME_APP_PROMOTION': 'device-mobile',
    };
    return iconMap[objValue] || 'eye';
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
            // Set default page if available
            if (options.value.pages?.length > 0) {
                ad.value.page_id = options.value.pages[0].id;
            }
        }
    } catch (error) {
        console.error('Error loading wizard options:', error);
        emit('error', 'Ma\'lumotlarni yuklashda xatolik');
    } finally {
        loading.value = false;
    }
};

const nextStep = () => {
    if (canProceed.value && currentStep.value < totalSteps) {
        // Auto-fill names based on campaign name
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
    // Set default optimization goal
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
    } catch (error) {
        console.error('Error searching interests:', error);
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
    } catch (error) {
        console.error('Error searching locations:', error);
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
            region: location.region,
            primary_city: location.primary_city,
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

    adSet.value.targeting.geo_locations = geoLocations;
};

// Behavior search
const searchBehaviors = debounce(async () => {
    if (behaviorQuery.value.length < 2) {
        behaviorResults.value = [];
        return;
    }
    searchingBehaviors.value = true;
    try {
        const response = await axios.get('/integrations/meta/api/search-behaviors', {
            params: { q: behaviorQuery.value, business_id: props.businessId }
        });
        behaviorResults.value = response.data.data || [];
    } catch (error) {
        console.error('Error searching behaviors:', error);
    } finally {
        searchingBehaviors.value = false;
    }
}, 300);

const addBehavior = (behavior) => {
    if (!selectedBehaviors.value.find(b => b.id === behavior.id)) {
        selectedBehaviors.value.push(behavior);
        updateTargeting();
        fetchAudienceEstimate();
    }
    behaviorQuery.value = '';
    behaviorResults.value = [];
};

const removeBehavior = (behavior) => {
    selectedBehaviors.value = selectedBehaviors.value.filter(b => b.id !== behavior.id);
    updateTargeting();
    fetchAudienceEstimate();
};

// Load interest category
const loadCategoryInterests = async (category) => {
    selectedInterestCategory.value = category;
    loadingCategoryInterests.value = true;
    try {
        const response = await axios.get('/integrations/meta/api/browse-interests', {
            params: { category: category.id, business_id: props.businessId }
        });
        categoryInterests.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading category interests:', error);
        categoryInterests.value = [];
    } finally {
        loadingCategoryInterests.value = false;
    }
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

const toggleEducation = (value) => {
    const index = demographics.value.education_statuses.indexOf(value);
    if (index > -1) {
        demographics.value.education_statuses.splice(index, 1);
    } else {
        demographics.value.education_statuses.push(value);
    }
    updateTargeting();
    fetchAudienceEstimate();
};

const toggleRelationship = (value) => {
    const index = demographics.value.relationship_statuses.indexOf(value);
    if (index > -1) {
        demographics.value.relationship_statuses.splice(index, 1);
    } else {
        demographics.value.relationship_statuses.push(value);
    }
    updateTargeting();
    fetchAudienceEstimate();
};

const togglePlacement = (placement) => {
    adSet.value.placements[placement] = !adSet.value.placements[placement];
    // If any specific placement is changed, disable automatic
    if (placement !== 'automatic') {
        adSet.value.placements.automatic = false;
    }
    // If automatic is enabled, enable all recommended placements
    if (placement === 'automatic' && adSet.value.placements.automatic) {
        // Facebook
        adSet.value.placements.facebook_feed = true;
        adSet.value.placements.facebook_marketplace = true;
        adSet.value.placements.facebook_video_feeds = true;
        adSet.value.placements.facebook_stories = true;
        adSet.value.placements.facebook_reels = true;
        adSet.value.placements.facebook_right_column = false;
        adSet.value.placements.facebook_search = false;
        // Instagram
        adSet.value.placements.instagram_feed = true;
        adSet.value.placements.instagram_stories = true;
        adSet.value.placements.instagram_reels = true;
        adSet.value.placements.instagram_explore = true;
        adSet.value.placements.instagram_shop = false;
        // Others
        adSet.value.placements.audience_network = false;
        adSet.value.placements.messenger_inbox = false;
        adSet.value.placements.messenger_stories = false;
    }
};

// Toggle all placements for a platform
const togglePlatformPlacements = (platform) => {
    const fbPlacements = ['facebook_feed', 'facebook_marketplace', 'facebook_video_feeds', 'facebook_stories', 'facebook_reels', 'facebook_right_column', 'facebook_search'];
    const igPlacements = ['instagram_feed', 'instagram_stories', 'instagram_reels', 'instagram_explore', 'instagram_shop'];
    const messengerPlacements = ['messenger_inbox', 'messenger_stories'];

    let placements = [];
    if (platform === 'facebook') placements = fbPlacements;
    else if (platform === 'instagram') placements = igPlacements;
    else if (platform === 'messenger') placements = messengerPlacements;

    // Check if any placement is enabled
    const anyEnabled = placements.some(p => adSet.value.placements[p]);

    // Toggle all
    placements.forEach(p => {
        adSet.value.placements[p] = !anyEnabled;
    });

    adSet.value.placements.automatic = false;
};

// Check if platform has any enabled placements
const isPlatformEnabled = (platform) => {
    const fbPlacements = ['facebook_feed', 'facebook_marketplace', 'facebook_video_feeds', 'facebook_stories', 'facebook_reels', 'facebook_right_column', 'facebook_search'];
    const igPlacements = ['instagram_feed', 'instagram_stories', 'instagram_reels', 'instagram_explore', 'instagram_shop'];
    const messengerPlacements = ['messenger_inbox', 'messenger_stories'];

    let placements = [];
    if (platform === 'facebook') placements = fbPlacements;
    else if (platform === 'instagram') placements = igPlacements;
    else if (platform === 'messenger') placements = messengerPlacements;

    return placements.some(p => adSet.value.placements[p]);
};

// Get enabled placements count
const getEnabledPlacementsCount = () => {
    const allPlacements = Object.keys(adSet.value.placements).filter(k => k !== 'automatic');
    return allPlacements.filter(p => adSet.value.placements[p]).length;
};

const updateTargeting = () => {
    const targeting = adSet.value.targeting;

    // Update interests
    if (selectedInterests.value.length > 0) {
        targeting.interests = selectedInterests.value.map(i => ({
            id: i.id,
            name: i.name,
        }));
    } else {
        delete targeting.interests;
    }

    // Update behaviors
    if (selectedBehaviors.value.length > 0) {
        targeting.behaviors = selectedBehaviors.value.map(b => ({
            id: b.id,
            name: b.name,
        }));
    } else {
        delete targeting.behaviors;
    }

    // Update demographics
    if (demographics.value.education_statuses.length > 0) {
        targeting.education_statuses = demographics.value.education_statuses;
    } else {
        delete targeting.education_statuses;
    }

    if (demographics.value.relationship_statuses.length > 0) {
        targeting.relationship_statuses = demographics.value.relationship_statuses;
    } else {
        delete targeting.relationship_statuses;
    }

    // Update custom locations with radius
    if (customLocations.value.length > 0) {
        targeting.geo_locations.custom_locations = customLocations.value.map(loc => ({
            latitude: loc.lat,
            longitude: loc.lng,
            radius: loc.radius,
            distance_unit: 'kilometer',
            name: loc.name,
        }));
    } else {
        delete targeting.geo_locations.custom_locations;
    }

    // Update excluded locations
    if (excludedLocations.value.length > 0) {
        if (!targeting.excluded_geo_locations) {
            targeting.excluded_geo_locations = {};
        }
        targeting.excluded_geo_locations.custom_locations = excludedLocations.value.map(loc => ({
            latitude: loc.lat,
            longitude: loc.lng,
            radius: loc.radius,
            distance_unit: 'kilometer',
            name: loc.name,
        }));
    } else {
        delete targeting.excluded_geo_locations;
    }
};

// Audience estimate
const fetchAudienceEstimate = debounce(async () => {
    audienceEstimate.value.loading = true;
    try {
        const response = await axios.post('/integrations/meta/api/reach-estimate', {
            business_id: props.businessId,
            targeting: adSet.value.targeting,
        });
        if (response.data.success) {
            audienceEstimate.value.users_lower_bound = response.data.users_lower_bound || 0;
            audienceEstimate.value.users_upper_bound = response.data.users_upper_bound || 0;
        }
    } catch (error) {
        console.error('Error fetching audience estimate:', error);
    } finally {
        audienceEstimate.value.loading = false;
    }
}, 500);

// Format audience number
const formatAudienceNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(0) + 'K';
    }
    return num.toLocaleString();
};

// Map functions
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

    // Add tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map.value);

    // Add click handler for adding locations
    map.value.on('click', handleMapClick);

    // Update markers for selected countries
    updateMapMarkers();

    // Update circles for custom locations
    updateMapCircles();
};

const updateMapMarkers = () => {
    if (!map.value) return;

    // Clear existing markers
    mapMarkers.value.forEach(marker => marker.remove());
    mapMarkers.value = [];

    // Add markers for selected countries
    const selectedCountries = adSet.value.targeting.geo_locations.countries;
    const bounds = [];

    selectedCountries.forEach(code => {
        const coords = countryCoordinates[code];
        if (coords) {
            // Create custom icon
            const icon = L.divIcon({
                className: 'custom-map-marker',
                html: `<div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-lg border-2 border-white">${code}</div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });

            const marker = L.marker([coords.lat, coords.lng], { icon })
                .addTo(map.value)
                .bindPopup(`<div class="font-medium">${coords.name}</div>`);

            mapMarkers.value.push(marker);
            bounds.push([coords.lat, coords.lng]);
        }
    });

    // Add markers for selected cities/regions
    selectedLocations.value.forEach(loc => {
        if (loc.latitude && loc.longitude) {
            const icon = L.divIcon({
                className: 'custom-map-marker',
                html: `<div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg border-2 border-white"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg></div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
            });

            const marker = L.marker([loc.latitude, loc.longitude], { icon })
                .addTo(map.value)
                .bindPopup(`<div class="font-medium">${loc.name}</div><div class="text-xs text-gray-500">${loc.type === 'city' ? 'Shahar' : 'Viloyat'}</div>`);

            mapMarkers.value.push(marker);
            bounds.push([loc.latitude, loc.longitude]);
        }
    });

    // Fit map to bounds if we have markers
    if (bounds.length > 0) {
        if (bounds.length === 1) {
            map.value.setView(bounds[0], 6);
        } else {
            map.value.fitBounds(bounds, { padding: [50, 50] });
        }
    }
};

const destroyMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
        mapMarkers.value = [];
        mapCircles.value = [];
    }
};

// Reverse geocoding using Nominatim
const reverseGeocode = async (lat, lng) => {
    const cacheKey = `${lat.toFixed(4)}_${lng.toFixed(4)}`;
    if (geocodeCache.value[cacheKey]) {
        return geocodeCache.value[cacheKey];
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`,
            { headers: { 'Accept-Language': 'uz,ru,en' } }
        );
        const data = await response.json();

        const result = {
            name: data.address?.city || data.address?.town || data.address?.village || data.address?.county || data.display_name?.split(',')[0] || 'Noma\'lum joy',
            fullName: data.display_name || '',
            country: data.address?.country || '',
            countryCode: data.address?.country_code?.toUpperCase() || '',
            type: data.address?.city ? 'city' : data.address?.town ? 'town' : 'location',
        };

        geocodeCache.value[cacheKey] = result;
        return result;
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        return { name: `${lat.toFixed(4)}, ${lng.toFixed(4)}`, fullName: '', country: '', countryCode: '', type: 'location' };
    }
};

// Add custom location with radius from map click
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
            type: geoData.type,
        };

        if (isExcluded) {
            excludedLocations.value.push(location);
        } else {
            customLocations.value.push(location);
        }

        updateMapCircles();
        updateTargeting();
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
    updateTargeting();
    fetchAudienceEstimate();
};

// Update location radius
const updateLocationRadius = (locationId, newRadius, isExcluded = false) => {
    const locations = isExcluded ? excludedLocations.value : customLocations.value;
    const location = locations.find(l => l.id === locationId);
    if (location) {
        location.radius = newRadius;
        updateMapCircles();
        updateTargeting();
        fetchAudienceEstimate();
    }
};

// Update map circles for custom locations
const updateMapCircles = () => {
    if (!map.value) return;

    // Clear existing circles
    mapCircles.value.forEach(circle => circle.remove());
    mapCircles.value = [];

    // Add circles for included locations (blue)
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
                <div class="font-medium">${loc.name}</div>
                <div class="text-xs text-gray-500">${loc.fullName || ''}</div>
                <div class="text-sm mt-1">Radius: ${loc.radius} km</div>
            </div>
        `);

        mapCircles.value.push(circle);

        // Add center marker
        const icon = L.divIcon({
            className: 'custom-map-marker',
            html: `<div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                </svg>
            </div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });
        const marker = L.marker([loc.lat, loc.lng], { icon }).addTo(map.value);
        mapCircles.value.push(marker);
    });

    // Add circles for excluded locations (red)
    excludedLocations.value.forEach(loc => {
        const circle = L.circle([loc.lat, loc.lng], {
            radius: loc.radius * 1000,
            color: '#ef4444',
            fillColor: '#ef4444',
            fillOpacity: 0.2,
            weight: 2,
            dashArray: '5, 5',
        }).addTo(map.value);

        circle.bindPopup(`
            <div class="p-2">
                <div class="font-medium text-red-600">Istisno: ${loc.name}</div>
                <div class="text-xs text-gray-500">${loc.fullName || ''}</div>
                <div class="text-sm mt-1">Radius: ${loc.radius} km</div>
            </div>
        `);

        mapCircles.value.push(circle);

        // Add center marker (red)
        const icon = L.divIcon({
            className: 'custom-map-marker',
            html: `<div class="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });
        const marker = L.marker([loc.lat, loc.lng], { icon }).addTo(map.value);
        mapCircles.value.push(marker);
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

const toggleMapView = () => {
    showMap.value = !showMap.value;
    if (showMap.value) {
        nextTick(() => {
            initMap();
        });
    }
};

// Image upload
const handleImageSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        imageFile.value = file;
        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            ad.value.image_preview = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const uploadImage = async () => {
    if (!imageFile.value) return;

    uploadingImage.value = true;
    try {
        const reader = new FileReader();
        reader.onload = async (e) => {
            const base64 = e.target.result;
            const response = await axios.post('/integrations/meta/api/upload-image', {
                business_id: props.businessId,
                image: base64,
            });
            if (response.data.success) {
                ad.value.image_hash = response.data.image_hash;
            } else {
                emit('error', 'Rasm yuklashda xatolik');
            }
            uploadingImage.value = false;
        };
        reader.readAsDataURL(imageFile.value);
    } catch (error) {
        console.error('Error uploading image:', error);
        emit('error', 'Rasm yuklashda xatolik');
        uploadingImage.value = false;
    }
};

// Submit
const submitAd = async () => {
    submitting.value = true;
    try {
        const payload = {
            business_id: props.businessId,
            // Campaign
            campaign_name: campaign.value.name,
            objective: campaign.value.objective,
            // AdSet
            adset_name: adSet.value.name,
            daily_budget: adSet.value.daily_budget,
            optimization_goal: adSet.value.optimization_goal,
            targeting: adSet.value.targeting,
            start_time: adSet.value.start_time || undefined,
            end_time: adSet.value.end_time || undefined,
            // Ad
            ad_name: ad.value.name,
            page_id: ad.value.page_id,
            primary_text: ad.value.primary_text,
            headline: ad.value.headline,
            description: ad.value.description,
            link: ad.value.link,
            call_to_action: ad.value.call_to_action,
            image_hash: ad.value.image_hash || undefined,
            status: 'PAUSED',
        };

        const response = await axios.post('/integrations/meta/api/full-ad/create', payload);

        if (response.data.success) {
            emit('created', response.data);
            closeWizard();
        } else {
            emit('error', response.data.message || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error creating ad:', error);
        emit('error', error.response?.data?.message || 'Reklama yaratishda xatolik');
    } finally {
        submitting.value = false;
    }
};

const closeWizard = () => {
    currentStep.value = 1;
    campaign.value = { name: '', objective: '' };
    adSet.value = {
        name: '',
        daily_budget: 5,
        optimization_goal: '',
        targeting: {
            geo_locations: { countries: ['UZ'], cities: [], regions: [] },
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
            instagram_feed: true,
            instagram_stories: true,
            instagram_reels: true,
            instagram_explore: true,
            instagram_shop: false,
            audience_network: false,
            messenger_inbox: false,
            messenger_stories: false,
        },
        start_time: '',
        end_time: '',
        bid_strategy: 'LOWEST_COST_WITHOUT_CAP',
    };
    ad.value = {
        name: '',
        page_id: options.value.pages?.[0]?.id || '',
        primary_text: '',
        headline: '',
        description: '',
        link: '',
        call_to_action: 'LEARN_MORE',
        image_hash: '',
        image_preview: '',
        format: 'single_image',
    };
    previewFormat.value = 'facebook_feed';
    selectedInterests.value = [];
    selectedBehaviors.value = [];
    selectedLocations.value = [];
    customLocations.value = [];
    excludedLocations.value = [];
    mapClickMode.value = 'add';
    imageFile.value = null;
    destroyMap();
    emit('close');
};

// Watchers
watch(() => props.show, (newVal) => {
    if (newVal) {
        loadOptions();
    }
});

watch(interestQuery, searchInterests);
watch(locationQuery, searchLocations);
watch(behaviorQuery, searchBehaviors);

// Watch for country changes to update map
watch(() => adSet.value.targeting.geo_locations.countries, () => {
    updateMapMarkers();
}, { deep: true });

// Watch for selected locations changes
watch(selectedLocations, () => {
    updateMapMarkers();
}, { deep: true });

// Watch for custom locations changes
watch([customLocations, excludedLocations], () => {
    updateMapCircles();
}, { deep: true });

// Watch for targeting section change to init map
watch(activeTargetingSection, (newVal) => {
    if (newVal === 'location' && showMap.value) {
        nextTick(() => {
            if (!map.value) {
                initMap();
            } else {
                map.value.invalidateSize();
            }
        });
    }
});

// Watch for step change
watch(currentStep, (newStep) => {
    if (newStep === 2) {
        nextTick(() => {
            if (activeTargetingSection.value === 'location' && showMap.value) {
                initMap();
            }
        });
    }
});

// Lifecycle
onMounted(() => {
    if (props.show) {
        loadOptions();
    }
});

onUnmounted(() => {
    destroyMap();
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black/60" @click="closeWizard"></div>

        <!-- Modal -->
        <div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden z-10">
            <!-- Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Yangi reklama yaratish</h2>
                        <p class="text-sm text-blue-100 mt-1">Qadam {{ currentStep }} / {{ totalSteps }}</p>
                    </div>
                    <button @click="closeWizard" class="p-2 hover:bg-white/20 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Progress bar -->
                <div class="mt-4 flex gap-2">
                    <div v-for="step in totalSteps" :key="step"
                        :class="[
                            'h-1 flex-1 rounded-full transition-all',
                            step <= currentStep ? 'bg-white' : 'bg-white/30'
                        ]"></div>
                </div>

                <!-- Step labels -->
                <div class="mt-2 flex justify-between text-xs text-blue-100">
                    <span :class="{ 'text-white font-medium': currentStep >= 1 }">Kampaniya</span>
                    <span :class="{ 'text-white font-medium': currentStep >= 2 }">Auditoriya</span>
                    <span :class="{ 'text-white font-medium': currentStep >= 3 }">Kreativ</span>
                    <span :class="{ 'text-white font-medium': currentStep >= 4 }">Tasdiqlash</span>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
                </div>

                <!-- Step 1: Campaign -->
                <div v-else-if="currentStep === 1" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kampaniya nomi
                        </label>
                        <input v-model="campaign.name" type="text" placeholder="Masalan: Yangi yil aksiyasi"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Kampaniya maqsadi
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <button v-for="obj in options.objectives" :key="obj.value"
                                @click="selectObjective(obj)"
                                :class="[
                                    'p-4 border-2 rounded-xl text-left transition-all',
                                    campaign.objective === obj.value
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                ]">
                                <div class="flex items-start gap-3">
                                    <div :class="[
                                        'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
                                        campaign.objective === obj.value ? getIconBgClass(obj.value) : 'bg-gray-100 dark:bg-gray-700'
                                    ]">
                                        <svg class="w-5 h-5" :class="campaign.objective === obj.value ? 'text-white' : 'text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getObjectiveIcon(getObjectiveIconName(obj.value))" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ obj.label }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ obj.description }}</div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: AdSet (Targeting & Budget) - Facebook Ads Manager style -->
                <div v-else-if="currentStep === 2" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Targeting sections (2 columns) -->
                        <div class="lg:col-span-2 space-y-4">
                            <!-- AdSet Name & Budget Row -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">AdSet nomi</label>
                                        <input v-model="adSet.name" type="text" placeholder="AdSet nomi"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Kunlik byudjet ($)</label>
                                        <input v-model.number="adSet.daily_budget" type="number" min="1" step="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Optimizatsiya</label>
                                        <select v-model="adSet.optimization_goal"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option v-for="goal in optimizationGoals" :key="goal.value" :value="goal.value">{{ goal.label }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Targeting Tabs -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <!-- Tab Navigation -->
                                <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                                    <button @click="activeTargetingSection = 'location'"
                                        :class="['flex-1 px-4 py-3 text-sm font-medium transition-all', activeTargetingSection === 'location' ? 'bg-white dark:bg-gray-800 text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        Joylashuv
                                    </button>
                                    <button @click="activeTargetingSection = 'demographics'"
                                        :class="['flex-1 px-4 py-3 text-sm font-medium transition-all', activeTargetingSection === 'demographics' ? 'bg-white dark:bg-gray-800 text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        Demografiya
                                    </button>
                                    <button @click="activeTargetingSection = 'interests'"
                                        :class="['flex-1 px-4 py-3 text-sm font-medium transition-all', activeTargetingSection === 'interests' ? 'bg-white dark:bg-gray-800 text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                        Qiziqishlar
                                    </button>
                                    <button @click="activeTargetingSection = 'behaviors'"
                                        :class="['flex-1 px-4 py-3 text-sm font-medium transition-all', activeTargetingSection === 'behaviors' ? 'bg-white dark:bg-gray-800 text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                        Xulq-atvor
                                    </button>
                                </div>

                                <!-- Tab Content -->
                                <div class="p-4">
                                    <!-- Location Tab -->
                                    <div v-if="activeTargetingSection === 'location'" class="space-y-4">
                                        <!-- Country chips -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mamlakatlar</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button v-for="country in options.countries" :key="country.code"
                                                    @click="toggleCountry(country.code)"
                                                    :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition border', adSet.targeting.geo_locations.countries.includes(country.code) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400']">
                                                    {{ country.name }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- City/Region search -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shahar yoki viloyat qo'shish</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                </div>
                                                <input v-model="locationQuery" type="text" placeholder="Shahar, viloyat qidirish..."
                                                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                                <div v-if="searchingLocations" class="absolute right-3 top-1/2 -translate-y-1/2">
                                                    <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </div>
                                            </div>
                                            <!-- Location results dropdown -->
                                            <div v-if="locationResults.length > 0" class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                <button v-for="loc in locationResults" :key="loc.key"
                                                    @click="addLocation(loc)"
                                                    class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-sm">
                                                    <span class="text-gray-900 dark:text-white">{{ loc.name }}</span>
                                                    <span class="text-xs text-gray-500 ml-2">{{ loc.type === 'city' ? 'Shahar' : loc.type === 'region' ? 'Viloyat' : loc.type }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Selected locations -->
                                        <div v-if="selectedLocations.length > 0" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanlangan joylashuvlar</label>
                                            <div class="flex flex-wrap gap-2">
                                                <span v-for="loc in selectedLocations" :key="loc.key"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-sm">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                                    {{ loc.name }}
                                                    <button @click="removeLocation(loc)" class="ml-1 hover:text-green-600">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Map Section - Like Facebook Ads Manager -->
                                        <div class="mt-4 space-y-3">
                                            <!-- Map Header with Controls -->
                                            <div class="flex items-center justify-between">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                                                    Xaritadan joy tanlash
                                                </label>
                                                <button @click="toggleMapView" class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                                    <svg v-if="showMap" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    {{ showMap ? 'Yashirish' : 'Ko\'rsatish' }}
                                                </button>
                                            </div>

                                            <transition name="fade">
                                                <div v-if="showMap" class="space-y-3">
                                                    <!-- Map Mode Buttons -->
                                                    <div class="flex items-center gap-2">
                                                        <button @click="setMapClickMode('add')"
                                                            :class="[
                                                                'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition border-2',
                                                                mapClickMode === 'add'
                                                                    ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-500 text-blue-700 dark:text-blue-300'
                                                                    : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-300'
                                                            ]">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                                            Qo'shish rejimi
                                                        </button>
                                                        <button @click="setMapClickMode('exclude')"
                                                            :class="[
                                                                'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition border-2',
                                                                mapClickMode === 'exclude'
                                                                    ? 'bg-red-50 dark:bg-red-900/30 border-red-500 text-red-700 dark:text-red-300'
                                                                    : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-red-300'
                                                            ]">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                            Istisno rejimi
                                                        </button>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                            <svg v-if="mapClickMode" class="w-4 h-4 inline animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2z" /></svg>
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
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Standart radius:</span>
                                                        <select v-model="defaultRadius" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                                            <option v-for="r in radiusOptions" :key="r" :value="r">{{ r }} km</option>
                                                        </select>
                                                    </div>

                                                    <!-- Map Container -->
                                                    <div class="relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600" :class="{ 'ring-2 ring-blue-500': mapClickMode === 'add', 'ring-2 ring-red-500': mapClickMode === 'exclude' }">
                                                        <div ref="mapContainer" class="w-full h-72" :class="{ 'cursor-crosshair': mapClickMode }"></div>

                                                        <!-- Map Legend -->
                                                        <div class="absolute bottom-2 left-2 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg px-3 py-2 text-xs shadow-lg">
                                                            <div class="flex flex-col gap-1.5">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                                                    <span class="text-gray-700 dark:text-gray-300">Kiritilgan hudud</span>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                                    <span class="text-gray-700 dark:text-gray-300">Istisno hudud</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Location Counter -->
                                                        <div v-if="customLocations.length > 0 || excludedLocations.length > 0"
                                                            class="absolute top-2 right-2 flex gap-2">
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
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            <span class="text-blue-600">+</span> Kiritilgan hududlar
                                                        </label>
                                                        <div class="space-y-2 max-h-40 overflow-y-auto">
                                                            <div v-for="loc in customLocations" :key="loc.id"
                                                                class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                                                <div class="flex items-center gap-2 flex-1">
                                                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="min-w-0">
                                                                        <div class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ loc.name }}</div>
                                                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ loc.country }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <select :value="loc.radius" @change="updateLocationRadius(loc.id, parseInt($event.target.value), false)"
                                                                        class="px-2 py-1 text-xs border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            <span class="text-red-600">-</span> Istisno qilingan hududlar
                                                        </label>
                                                        <div class="space-y-2 max-h-40 overflow-y-auto">
                                                            <div v-for="loc in excludedLocations" :key="loc.id"
                                                                class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                                                <div class="flex items-center gap-2 flex-1">
                                                                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="min-w-0">
                                                                        <div class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ loc.name }}</div>
                                                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ loc.country }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <select :value="loc.radius" @change="updateLocationRadius(loc.id, parseInt($event.target.value), true)"
                                                                        class="px-2 py-1 text-xs border border-red-300 dark:border-red-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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
                                            </transition>
                                        </div>
                                    </div>

                                    <!-- Demographics Tab -->
                                    <div v-else-if="activeTargetingSection === 'demographics'" class="space-y-5">
                                        <!-- Age -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yosh oralig'i</label>
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1">
                                                    <input v-model.number="adSet.targeting.age_min" type="range" min="13" max="65" @change="fetchAudienceEstimate"
                                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                                    <div class="text-center text-sm text-gray-600 dark:text-gray-400 mt-1">{{ adSet.targeting.age_min }} yosh</div>
                                                </div>
                                                <span class="text-gray-400">—</span>
                                                <div class="flex-1">
                                                    <input v-model.number="adSet.targeting.age_max" type="range" min="13" max="65" @change="fetchAudienceEstimate"
                                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                                    <div class="text-center text-sm text-gray-600 dark:text-gray-400 mt-1">{{ adSet.targeting.age_max }} yosh</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Gender -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jins</label>
                                            <div class="flex gap-2">
                                                <button @click="toggleGender(1)"
                                                    :class="['flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-medium transition border-2', adSet.targeting.genders.includes(1) ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 text-blue-700 dark:text-blue-300' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300']">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                    Erkak
                                                </button>
                                                <button @click="toggleGender(2)"
                                                    :class="['flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-medium transition border-2', adSet.targeting.genders.includes(2) ? 'bg-pink-50 dark:bg-pink-900/20 border-pink-500 text-pink-700 dark:text-pink-300' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300']">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                    Ayol
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Education -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ta'lim darajasi</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button v-for="edu in educationOptions" :key="edu.value"
                                                    @click="toggleEducation(edu.value)"
                                                    :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition border', demographics.education_statuses.includes(edu.value) ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-400' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-purple-300']">
                                                    {{ edu.label }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Relationship status -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Oilaviy holat</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button v-for="rel in relationshipOptions" :key="rel.value"
                                                    @click="toggleRelationship(rel.value)"
                                                    :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition border', demographics.relationship_statuses.includes(rel.value) ? 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 border-pink-400' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-pink-300']">
                                                    {{ rel.label }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Interests Tab -->
                                    <div v-else-if="activeTargetingSection === 'interests'" class="space-y-4">
                                        <!-- Interest search -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qiziqish qidirish</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                </div>
                                                <input v-model="interestQuery" type="text" placeholder="Sport, musiqa, biznes..."
                                                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                                <div v-if="searchingInterests" class="absolute right-3 top-1/2 -translate-y-1/2">
                                                    <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </div>
                                            </div>
                                            <!-- Interest results dropdown -->
                                            <div v-if="interestResults.length > 0" class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                <button v-for="interest in interestResults" :key="interest.id"
                                                    @click="addInterest(interest); fetchAudienceEstimate()"
                                                    class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-sm flex justify-between items-center">
                                                    <span class="text-gray-900 dark:text-white">{{ interest.name }}</span>
                                                    <span class="text-xs text-gray-500">{{ interest.audience_size_lower_bound ? formatAudienceNumber(interest.audience_size_lower_bound) + '+' : '' }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Interest categories (browse) -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yoki kategoriyadan tanlang</label>
                                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                                                <button v-for="cat in interestCategories" :key="cat.id"
                                                    @click="showInterestBrowser = true; loadCategoryInterests(cat)"
                                                    class="px-3 py-2 text-xs rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-center truncate">
                                                    {{ cat.name }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Selected interests -->
                                        <div v-if="selectedInterests.length > 0">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanlangan qiziqishlar</label>
                                            <div class="flex flex-wrap gap-2">
                                                <span v-for="interest in selectedInterests" :key="interest.id"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                                    {{ interest.name }}
                                                    <button @click="removeInterest(interest); fetchAudienceEstimate()" class="ml-1 hover:text-blue-600">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Behaviors Tab -->
                                    <div v-else-if="activeTargetingSection === 'behaviors'" class="space-y-4">
                                        <!-- Behavior search -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Xulq-atvor qidirish</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                </div>
                                                <input v-model="behaviorQuery" type="text" placeholder="Sayohat qiluvchilar, tadbirkorlar..."
                                                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                                <div v-if="searchingBehaviors" class="absolute right-3 top-1/2 -translate-y-1/2">
                                                    <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </div>
                                            </div>
                                            <!-- Behavior results dropdown -->
                                            <div v-if="behaviorResults.length > 0" class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                <button v-for="behavior in behaviorResults" :key="behavior.id"
                                                    @click="addBehavior(behavior)"
                                                    class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-sm flex justify-between items-center">
                                                    <span class="text-gray-900 dark:text-white">{{ behavior.name }}</span>
                                                    <span class="text-xs text-gray-500">{{ behavior.audience_size_lower_bound ? formatAudienceNumber(behavior.audience_size_lower_bound) + '+' : '' }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Popular behaviors -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mashhur xulq-atvorlar</label>
                                            <div class="grid grid-cols-2 gap-2 text-sm">
                                                <button class="px-3 py-2 text-left rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition">
                                                    <span class="font-medium">Onlayn xaridorlar</span>
                                                    <p class="text-xs text-gray-500 mt-0.5">Internet orqali xarid qiluvchilar</p>
                                                </button>
                                                <button class="px-3 py-2 text-left rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition">
                                                    <span class="font-medium">Sayohatchilar</span>
                                                    <p class="text-xs text-gray-500 mt-0.5">Tez-tez sayohat qiluvchilar</p>
                                                </button>
                                                <button class="px-3 py-2 text-left rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition">
                                                    <span class="font-medium">Tadbirkorlar</span>
                                                    <p class="text-xs text-gray-500 mt-0.5">Biznes egalar</p>
                                                </button>
                                                <button class="px-3 py-2 text-left rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition">
                                                    <span class="font-medium">Mobil foydalanuvchilar</span>
                                                    <p class="text-xs text-gray-500 mt-0.5">Smartfon orqali kirishadi</p>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Selected behaviors -->
                                        <div v-if="selectedBehaviors.length > 0">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanlangan xulq-atvorlar</label>
                                            <div class="flex flex-wrap gap-2">
                                                <span v-for="behavior in selectedBehaviors" :key="behavior.id"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200 rounded-full text-sm">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                                    {{ behavior.name }}
                                                    <button @click="removeBehavior(behavior)" class="ml-1 hover:text-orange-600">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Placements Preview (detailed in Step 3) -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Joylashtirish</label>
                                    <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                                        {{ adSet.placements.automatic ? 'Avtomatik' : getEnabledPlacementsCount() + ' ta' }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Batafsil joylashuvlarni keyingi qadamda (Kreativ) sozlash mumkin</p>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <span v-if="isPlatformEnabled('facebook')" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded text-xs">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        Facebook
                                    </span>
                                    <span v-if="isPlatformEnabled('instagram')" class="inline-flex items-center gap-1 px-2 py-1 bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 rounded text-xs">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        Instagram
                                    </span>
                                    <span v-if="isPlatformEnabled('messenger')" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded text-xs">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111S18.627 0 12 0z"/></svg>
                                        Messenger
                                    </span>
                                </div>
                            </div>

                            <!-- Schedule -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Jadval</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Boshlanish</label>
                                        <input v-model="adSet.start_time" type="datetime-local"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tugash (ixtiyoriy)</label>
                                        <input v-model="adSet.end_time" type="datetime-local"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Audience Summary -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-4 space-y-4">
                                <!-- Audience Size Estimate -->
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-5 text-white">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-medium">Auditoriya hajmi</h4>
                                        <div v-if="audienceEstimate.loading" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                                    </div>
                                    <div class="text-center py-4">
                                        <div class="text-4xl font-bold mb-1">
                                            {{ audienceEstimate.users_lower_bound > 0 ? formatAudienceNumber(audienceEstimate.users_lower_bound) + ' - ' + formatAudienceNumber(audienceEstimate.users_upper_bound) : '—' }}
                                        </div>
                                        <div class="text-blue-100 text-sm">Potensial foydalanuvchilar</div>
                                    </div>
                                    <!-- Audience meter -->
                                    <div class="mt-3">
                                        <div class="flex justify-between text-xs text-blue-100 mb-1">
                                            <span>Juda tor</span>
                                            <span>Juda keng</span>
                                        </div>
                                        <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                            <div class="h-full bg-white rounded-full transition-all duration-500"
                                                :style="{ width: audienceEstimate.users_upper_bound > 0 ? Math.min(Math.log10(audienceEstimate.users_upper_bound) * 10, 100) + '%' : '0%' }"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Targeting Summary -->
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Targeting xulosa</h4>
                                    <div class="space-y-3 text-sm">
                                        <!-- Locations -->
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">Joylashuv</div>
                                                <div class="text-gray-900 dark:text-white">
                                                    {{ adSet.targeting.geo_locations.countries.length > 0 ? adSet.targeting.geo_locations.countries.join(', ') : 'Tanlanmagan' }}
                                                    <span v-if="selectedLocations.length > 0" class="text-xs text-green-600"> + {{ selectedLocations.length }} joy</span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Age & Gender -->
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">Demografiya</div>
                                                <div class="text-gray-900 dark:text-white">
                                                    {{ adSet.targeting.age_min }}-{{ adSet.targeting.age_max }} yosh,
                                                    {{ adSet.targeting.genders.length === 2 ? 'Barchasi' : adSet.targeting.genders.includes(1) ? 'Erkaklar' : 'Ayollar' }}
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Interests -->
                                        <div v-if="selectedInterests.length > 0" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-pink-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">Qiziqishlar</div>
                                                <div class="text-gray-900 dark:text-white">{{ selectedInterests.length }} ta tanlangan</div>
                                            </div>
                                        </div>
                                        <!-- Behaviors -->
                                        <div v-if="selectedBehaviors.length > 0" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-orange-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">Xulq-atvorlar</div>
                                                <div class="text-gray-900 dark:text-white">{{ selectedBehaviors.length }} ta tanlangan</div>
                                            </div>
                                        </div>
                                        <!-- Budget -->
                                        <div class="flex items-start gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">Kunlik byudjet</div>
                                                <div class="text-gray-900 dark:text-white font-medium">${{ adSet.daily_budget }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Ad Creative - Professional Like Ads Manager -->
                <div v-else-if="currentStep === 3" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Creative Form (2 columns) -->
                        <div class="lg:col-span-2 space-y-4">
                            <!-- Ad Name & Page Selection -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    Reklama identifikatsiyasi
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Reklama nomi</label>
                                        <input v-model="ad.name" type="text" placeholder="Reklama nomini kiriting"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Facebook/Instagram sahifa</label>
                                        <select v-model="ad.page_id"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option v-for="page in options.pages" :key="page.id" :value="page.id">{{ page.name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Ad Creative Content -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    Kreativ kontent
                                </h4>

                                <!-- Image/Video upload -->
                                <div class="mb-4">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Media (rasm yoki video)</label>
                                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-blue-500 transition cursor-pointer"
                                        @click="$refs.fileInput.click()">
                                        <input ref="fileInput" type="file" accept="image/*,video/*" @change="handleImageSelect" class="hidden">
                                        <div v-if="ad.image_preview" class="relative">
                                            <img :src="ad.image_preview" class="max-h-40 mx-auto rounded-lg">
                                            <button @click.stop="ad.image_preview = ''; ad.image_hash = ''; imageFile = null"
                                                class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Rasm yoki video yuklash uchun bosing</p>
                                            <p class="text-xs text-gray-400">PNG, JPG, GIF, MP4 (max 30MB)</p>
                                        </div>
                                    </div>
                                    <div v-if="imageFile && !ad.image_hash" class="mt-2 flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ imageFile.name }}</span>
                                        <button @click="uploadImage" :disabled="uploadingImage"
                                            class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                            {{ uploadingImage ? 'Yuklanmoqda...' : 'Meta\'ga yuklash' }}
                                        </button>
                                    </div>
                                    <div v-if="ad.image_hash" class="mt-2 flex items-center gap-2 text-green-600 text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Media muvaffaqiyatli yuklandi
                                    </div>
                                </div>

                                <!-- Primary Text -->
                                <div class="mb-4">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        Asosiy matn <span class="text-red-500">*</span>
                                        <span class="text-gray-400 font-normal">({{ ad.primary_text.length }}/125 belgi tavsiya)</span>
                                    </label>
                                    <textarea v-model="ad.primary_text" rows="3" placeholder="Reklamangiz uchun jalb qiluvchi matn yozing..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                                </div>

                                <!-- Headline & Description -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Sarlavha <span class="text-gray-400 font-normal">({{ ad.headline.length }}/40)</span>
                                        </label>
                                        <input v-model="ad.headline" type="text" placeholder="Qisqa va jalb qiluvchi sarlavha"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Tavsif <span class="text-gray-400 font-normal">(ixtiyoriy)</span>
                                        </label>
                                        <input v-model="ad.description" type="text" placeholder="Qo'shimcha tavsif"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                </div>

                                <!-- Link & CTA -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Manzil URL <span class="text-red-500">*</span>
                                        </label>
                                        <input v-model="ad.link" type="url" placeholder="https://example.com"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Harakatga chaqiruv (CTA)</label>
                                        <select v-model="ad.call_to_action"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option v-for="cta in options.call_to_actions" :key="cta.value" :value="cta.value">{{ cta.label }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Placements Section -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        Joylashuvlar (Placements)
                                    </h4>
                                    <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                                        {{ getEnabledPlacementsCount() }} ta tanlangan
                                    </span>
                                </div>

                                <!-- Automatic placement option -->
                                <label class="flex items-center gap-3 p-3 mb-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl cursor-pointer border-2 transition"
                                    :class="adSet.placements.automatic ? 'border-blue-500' : 'border-transparent'">
                                    <input type="checkbox" v-model="adSet.placements.automatic" @change="togglePlacement('automatic')" class="w-5 h-5 rounded text-blue-600">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">Advantage+ joylashuvlar (tavsiya)</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Meta avtomatik ravishda eng yaxshi natija beradigan joylarni tanlaydi</div>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Eng yaxshi</span>
                                </label>

                                <!-- Manual placements -->
                                <div v-if="!adSet.placements.automatic" class="space-y-4">
                                    <!-- Facebook Placements -->
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                        <button @click="togglePlatformPlacements('facebook')"
                                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-900 transition">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                <span class="font-medium text-gray-900 dark:text-white">Facebook</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span :class="isPlatformEnabled('facebook') ? 'text-green-600' : 'text-gray-400'" class="text-sm">
                                                    {{ isPlatformEnabled('facebook') ? 'Faol' : 'O\'chiq' }}
                                                </span>
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </button>
                                        <div class="p-3 grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_feed" @change="togglePlacement('facebook_feed')" class="w-4 h-4 rounded text-blue-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Feed</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_stories" @change="togglePlacement('facebook_stories')" class="w-4 h-4 rounded text-blue-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Stories</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_reels" @change="togglePlacement('facebook_reels')" class="w-4 h-4 rounded text-blue-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Reels</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_marketplace" @change="togglePlacement('facebook_marketplace')" class="w-4 h-4 rounded text-blue-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Marketplace</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_video_feeds" @change="togglePlacement('facebook_video_feeds')" class="w-4 h-4 rounded text-blue-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Video Feeds</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.facebook_right_column" @change="togglePlacement('facebook_right_column')" class="w-4 h-4 rounded text-blue-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">O'ng ustun</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Instagram Placements -->
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                        <button @click="togglePlatformPlacements('instagram')"
                                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-900 transition">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-6 h-6 text-pink-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                                <span class="font-medium text-gray-900 dark:text-white">Instagram</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span :class="isPlatformEnabled('instagram') ? 'text-green-600' : 'text-gray-400'" class="text-sm">
                                                    {{ isPlatformEnabled('instagram') ? 'Faol' : 'O\'chiq' }}
                                                </span>
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </button>
                                        <div class="p-3 grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_feed" @change="togglePlacement('instagram_feed')" class="w-4 h-4 rounded text-pink-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Feed</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_stories" @change="togglePlacement('instagram_stories')" class="w-4 h-4 rounded text-pink-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Stories</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_reels" @change="togglePlacement('instagram_reels')" class="w-4 h-4 rounded text-pink-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Reels</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_explore" @change="togglePlacement('instagram_explore')" class="w-4 h-4 rounded text-pink-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Explore</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.instagram_shop" @change="togglePlacement('instagram_shop')" class="w-4 h-4 rounded text-pink-600">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Shop</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Messenger Placements -->
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                        <button @click="togglePlatformPlacements('messenger')"
                                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-900 transition">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-6 h-6 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111S18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.259L19.752 8l-6.561 6.963z"/></svg>
                                                <span class="font-medium text-gray-900 dark:text-white">Messenger</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span :class="isPlatformEnabled('messenger') ? 'text-green-600' : 'text-gray-400'" class="text-sm">
                                                    {{ isPlatformEnabled('messenger') ? 'Faol' : 'O\'chiq' }}
                                                </span>
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </button>
                                        <div class="p-3 grid grid-cols-2 gap-2">
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.messenger_inbox" @change="togglePlacement('messenger_inbox')" class="w-4 h-4 rounded text-blue-500">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Inbox</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                                <input type="checkbox" v-model="adSet.placements.messenger_stories" @change="togglePlacement('messenger_stories')" class="w-4 h-4 rounded text-blue-500">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Stories</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Live Preview -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-4 space-y-4">
                                <!-- Preview format selector -->
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
                                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                        <button v-for="fmt in previewFormats" :key="fmt.value" @click="previewFormat = fmt.value"
                                            :class="['px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition', previewFormat === fmt.value ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200']">
                                            {{ fmt.label }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Feed Preview -->
                                <div v-if="previewFormat === 'facebook_feed' || previewFormat === 'instagram_feed'"
                                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-lg">
                                    <!-- Page header -->
                                    <div class="flex items-center gap-3 p-3 border-b border-gray-100 dark:border-gray-800">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                            {{ options.pages?.find(p => p.id === ad.page_id)?.name?.[0] || 'P' }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                                {{ options.pages?.find(p => p.id === ad.page_id)?.name || 'Sahifa nomi' }}
                                            </div>
                                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                                <span>Homiylik qilingan</span>
                                                <span>·</span>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z"/></svg>
                                            </div>
                                        </div>
                                        <button class="p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                        </button>
                                    </div>

                                    <!-- Post text -->
                                    <div class="px-3 py-2">
                                        <p class="text-gray-900 dark:text-white text-sm whitespace-pre-wrap line-clamp-3">
                                            {{ ad.primary_text || 'Reklama matni bu yerda ko\'rinadi...' }}
                                        </p>
                                    </div>

                                    <!-- Image/Media -->
                                    <div class="relative aspect-square bg-gray-100 dark:bg-gray-800">
                                        <img v-if="ad.image_preview" :src="ad.image_preview" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <div class="text-center text-gray-400">
                                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                <span class="text-sm">Rasm</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Link preview card -->
                                    <div class="mx-3 my-2 bg-gray-50 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                        <div class="p-3">
                                            <div class="text-xs text-gray-500 uppercase mb-1">{{ ad.link ? (ad.link.replace(/^https?:\/\//, '').split('/')[0]) : 'EXAMPLE.COM' }}</div>
                                            <div class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-1">{{ ad.headline || 'Sarlavha' }}</div>
                                            <div v-if="ad.description" class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ ad.description }}</div>
                                        </div>
                                    </div>

                                    <!-- CTA button -->
                                    <div class="px-3 pb-3">
                                        <button class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm transition">
                                            {{ options.call_to_actions?.find(c => c.value === ad.call_to_action)?.label || 'Batafsil' }}
                                        </button>
                                    </div>

                                    <!-- Engagement bar -->
                                    <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 dark:border-gray-800 text-gray-500 text-xs">
                                        <div class="flex items-center gap-1">
                                            <div class="flex -space-x-1">
                                                <span class="w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">👍</span>
                                                <span class="w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">❤️</span>
                                            </div>
                                            <span>124</span>
                                        </div>
                                        <span>15 izoh · 8 ulashish</span>
                                    </div>
                                </div>

                                <!-- Story Preview -->
                                <div v-else-if="previewFormat === 'instagram_story' || previewFormat === 'facebook_story'"
                                    class="relative bg-black rounded-2xl overflow-hidden shadow-lg" style="aspect-ratio: 9/16; max-height: 500px;">
                                    <!-- Background image -->
                                    <div class="absolute inset-0">
                                        <img v-if="ad.image_preview" :src="ad.image_preview" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                    </div>

                                    <!-- Overlay gradient -->
                                    <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-black/60"></div>

                                    <!-- Top bar -->
                                    <div class="absolute top-0 inset-x-0 p-3">
                                        <div class="h-0.5 bg-white/30 rounded-full mb-3">
                                            <div class="h-full w-1/3 bg-white rounded-full"></div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-pink-500 rounded-full p-0.5">
                                                <div class="w-full h-full bg-white rounded-full flex items-center justify-center text-xs font-bold">
                                                    {{ options.pages?.find(p => p.id === ad.page_id)?.name?.[0] || 'P' }}
                                                </div>
                                            </div>
                                            <span class="text-white text-sm font-medium">{{ options.pages?.find(p => p.id === ad.page_id)?.name || 'Sahifa' }}</span>
                                            <span class="text-white/60 text-xs">Homiylik qilingan</span>
                                        </div>
                                    </div>

                                    <!-- Bottom content -->
                                    <div class="absolute bottom-0 inset-x-0 p-4 space-y-3">
                                        <p class="text-white text-sm line-clamp-2">{{ ad.primary_text || 'Reklama matni...' }}</p>
                                        <button class="w-full py-2.5 bg-white text-black rounded-full font-semibold text-sm">
                                            {{ options.call_to_actions?.find(c => c.value === ad.call_to_action)?.label || 'Batafsil' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Preview tips -->
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-3">
                                    <div class="flex gap-2">
                                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <div class="text-xs text-amber-800 dark:text-amber-200">
                                            <p class="font-medium mb-1">Ko'rinish bo'yicha maslahat</p>
                                            <p>Yaxshi reklama: aniq rasm, qisqa matn (125 belgigacha), jalb qiluvchi CTA</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review - Professional Summary -->
                <div v-else-if="currentStep === 4" class="space-y-6">
                    <!-- Status Banner -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 text-white">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-1">Reklamangiz tayyor!</h3>
                                <p class="text-green-100">
                                    Barcha sozlamalar to'g'ri kiritildi. Reklama <span class="font-semibold">PAUZADA</span> yaratiladi va keyin uni faollashtirish mumkin bo'ladi.
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-green-100">Taxminiy qamrov</div>
                                <div class="text-2xl font-bold">
                                    {{ audienceEstimate.users_lower_bound > 0 ? formatAudienceNumber(audienceEstimate.users_lower_bound) + ' - ' + formatAudienceNumber(audienceEstimate.users_upper_bound) : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Details (2 columns) -->
                        <div class="lg:col-span-2 space-y-4">
                            <!-- Campaign Details -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 bg-purple-50 dark:bg-purple-900/20 border-b border-purple-100 dark:border-purple-900/30 flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </div>
                                    <h4 class="font-semibold text-purple-900 dark:text-purple-100">1. Kampaniya</h4>
                                </div>
                                <div class="p-4">
                                    <dl class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Kampaniya nomi</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ campaign.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Maqsad</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                <span :class="getIconBgClass(campaign.objective)" class="w-6 h-6 rounded flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getObjectiveIcon(getObjectiveIconName(campaign.objective))" />
                                                    </svg>
                                                </span>
                                                {{ options.objectives?.find(o => o.value === campaign.objective)?.label }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Audience & Budget Details -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-900/30 flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                    <h4 class="font-semibold text-blue-900 dark:text-blue-100">2. Auditoriya va Byudjet</h4>
                                </div>
                                <div class="p-4">
                                    <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Kunlik byudjet</dt>
                                            <dd class="font-bold text-xl text-green-600">${{ adSet.daily_budget }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Joylashuv</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">
                                                {{ adSet.targeting.geo_locations.countries.join(', ') }}
                                                <span v-if="customLocations.length > 0" class="text-blue-600"> +{{ customLocations.length }} joy</span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Yosh oralig'i</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ adSet.targeting.age_min }} - {{ adSet.targeting.age_max }} yosh</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Jins</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">
                                                {{ adSet.targeting.genders.length === 2 ? 'Barchasi' : adSet.targeting.genders.includes(1) ? 'Erkaklar' : 'Ayollar' }}
                                            </dd>
                                        </div>
                                        <div v-if="selectedInterests.length > 0">
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Qiziqishlar</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ selectedInterests.length }} ta</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Optimizatsiya</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">
                                                {{ optimizationGoals.find(g => g.value === adSet.optimization_goal)?.label || adSet.optimization_goal }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Placements Summary -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-900/30 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        </div>
                                        <h4 class="font-semibold text-green-900 dark:text-green-100">3. Joylashuvlar</h4>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 text-xs rounded-full">
                                        {{ adSet.placements.automatic ? 'Avtomatik' : getEnabledPlacementsCount() + ' ta tanlangan' }}
                                    </span>
                                </div>
                                <div class="p-4">
                                    <div v-if="adSet.placements.automatic" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span>Advantage+ joylashuvlar - Meta eng yaxshi joylarni avtomatik tanlaydi</span>
                                    </div>
                                    <div v-else class="flex flex-wrap gap-2">
                                        <span v-if="isPlatformEnabled('facebook')" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded text-xs">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                            Facebook
                                        </span>
                                        <span v-if="isPlatformEnabled('instagram')" class="inline-flex items-center gap-1 px-2 py-1 bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 rounded text-xs">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                            Instagram
                                        </span>
                                        <span v-if="isPlatformEnabled('messenger')" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded text-xs">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111S18.627 0 12 0z"/></svg>
                                            Messenger
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Ad Creative Summary -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-100 dark:border-orange-900/30 flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <h4 class="font-semibold text-orange-900 dark:text-orange-100">4. Reklama kreativ</h4>
                                </div>
                                <div class="p-4">
                                    <dl class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Reklama nomi</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ ad.name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Sahifa</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ options.pages?.find(p => p.id === ad.page_id)?.name }}</dd>
                                        </div>
                                        <div class="col-span-2">
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Asosiy matn</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white line-clamp-2">{{ ad.primary_text }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Manzil URL</dt>
                                            <dd class="font-medium text-blue-600 truncate">{{ ad.link }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">CTA tugma</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ options.call_to_actions?.find(c => c.value === ad.call_to_action)?.label }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Media</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                <span v-if="ad.image_hash" class="text-green-600 flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    Yuklangan
                                                </span>
                                                <span v-else class="text-gray-400">Yuklanmagan</span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Ad Preview -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-4 space-y-4">
                                <!-- Mini preview -->
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                        <h4 class="font-medium text-gray-900 dark:text-white text-sm">Reklama ko'rinishi</h4>
                                    </div>
                                    <div class="p-3">
                                        <!-- Mini Feed preview -->
                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 text-xs">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                                    {{ options.pages?.find(p => p.id === ad.page_id)?.name?.[0] || 'P' }}
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ options.pages?.find(p => p.id === ad.page_id)?.name || 'Sahifa' }}</div>
                                                    <div class="text-gray-500 text-[10px]">Homiylik qilingan</div>
                                                </div>
                                            </div>
                                            <p class="text-gray-700 dark:text-gray-300 line-clamp-2 mb-2">{{ ad.primary_text || 'Matn...' }}</p>
                                            <div v-if="ad.image_preview" class="aspect-video rounded overflow-hidden mb-2">
                                                <img :src="ad.image_preview" class="w-full h-full object-cover">
                                            </div>
                                            <div v-else class="aspect-video bg-gray-200 dark:bg-gray-700 rounded mb-2 flex items-center justify-center text-gray-400">
                                                Rasm
                                            </div>
                                            <button class="w-full py-1.5 bg-blue-600 text-white rounded text-xs font-medium">
                                                {{ options.call_to_actions?.find(c => c.value === ad.call_to_action)?.label || 'Batafsil' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cost estimate -->
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-4 text-white">
                                    <h4 class="font-medium mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        Byudjet taxmini
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-blue-100">Kunlik:</span>
                                            <span class="font-bold">${{ adSet.daily_budget }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-100">Haftalik:</span>
                                            <span class="font-bold">${{ (adSet.daily_budget * 7).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t border-white/20 pt-2 mt-2">
                                            <span class="text-blue-100">Oylik:</span>
                                            <span class="font-bold text-lg">${{ (adSet.daily_budget * 30).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Warning/Info box -->
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                    <div class="flex gap-3">
                                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <div class="text-sm">
                                            <p class="font-medium text-amber-800 dark:text-amber-200 mb-1">Eslatma</p>
                                            <p class="text-amber-700 dark:text-amber-300">Reklama <strong>PAUZADA</strong> yaratiladi. Uni faollashtirish uchun Meta Ads Manager'ga o'ting.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <button v-if="currentStep > 1" @click="prevStep"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Orqaga
                </button>
                <div v-else></div>

                <div class="flex items-center gap-3">
                    <button @click="closeWizard"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 font-medium">
                        Bekor qilish
                    </button>

                    <button v-if="currentStep < totalSteps" @click="nextStep" :disabled="!canProceed"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center gap-2">
                        Keyingisi
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <button v-else @click="submitAd" :disabled="submitting"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium flex items-center gap-2">
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
</template>

<style scoped>
/* Map transitions */
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Custom map marker styles */
:deep(.custom-map-marker) {
    background: transparent;
    border: none;
}

/* Cursor crosshair for map click mode */
.cursor-crosshair :deep(.leaflet-container) {
    cursor: crosshair !important;
}

.cursor-crosshair :deep(.leaflet-interactive) {
    cursor: crosshair !important;
}

/* Leaflet popup customization */
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

:deep(.leaflet-popup-content) {
    margin: 8px 12px;
}

:deep(.leaflet-container) {
    font-family: inherit;
}

/* Range slider styling */
input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
