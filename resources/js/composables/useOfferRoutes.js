/**
 * Composable for handling offer routes across different panels
 * Converts panelType to correct route prefix
 */
export function useOfferRoutes(panelType) {
    /**
     * Get the route prefix based on panel type
     * saleshead -> sales-head (route uses hyphen)
     */
    const getRoutePrefix = () => {
        const prefixMap = {
            saleshead: 'sales-head',
            business: 'business',
            marketing: 'marketing',
            finance: 'finance',
            operator: 'operator',
        };
        return prefixMap[panelType] || panelType;
    };

    /**
     * Get full route name for an action
     */
    const getRouteName = (action) => {
        const prefix = getRoutePrefix();
        return `${prefix}.offers.${action}`;
    };

    /**
     * Get route URL for an action with optional params
     */
    const getRoute = (action, params = null) => {
        const routeName = getRouteName(action);
        return params ? route(routeName, params) : route(routeName);
    };

    return {
        getRoutePrefix,
        getRouteName,
        getRoute,
    };
}
