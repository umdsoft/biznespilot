<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;

/**
 * Trait for Store controllers to detect the current panel type from route prefix.
 *
 * This allows the same store controllers (Business\Store*Controller) to be reused
 * across business, operator, and saleshead panels with correct panelType and route names.
 */
trait HasStorePanelType
{
    /**
     * Detect the panel type from the current route prefix.
     *
     * Returns: 'business', 'operator', or 'sales-head'
     */
    protected function getStorePanelType(): string
    {
        $prefix = request()->route()?->getPrefix() ?? '';

        if (str_contains($prefix, 'operator')) {
            return 'operator';
        }

        if (str_contains($prefix, 'sales-head')) {
            return 'sales-head';
        }

        return 'business';
    }

    /**
     * Get the Inertia panelType value for Vue components.
     *
     * Maps route prefix to the panelType prop used by Vue layouts.
     */
    protected function getStorePanelTypeForInertia(): string
    {
        return $this->getStorePanelType();
    }

    /**
     * Generate a store route name with the correct panel prefix.
     *
     * Example: storeRoute('orders.index') returns 'operator.store.orders.index'
     * when accessed from the operator panel.
     */
    protected function storeRoute(string $routeSuffix): string
    {
        $panel = $this->getStorePanelType();

        return "{$panel}.store.{$routeSuffix}";
    }

    /**
     * Generate a redirect to a store route with the correct panel prefix.
     */
    protected function storeRedirect(string $routeSuffix, array $parameters = [])
    {
        return redirect()->route($this->storeRoute($routeSuffix), $parameters);
    }

    /**
     * Redirect to the store setup wizard for the current panel.
     * Only business panel has setup access; other panels redirect to their dashboard.
     */
    protected function redirectToStoreSetup(string $errorMessage = null)
    {
        $panel = $this->getStorePanelType();

        // Only business panel can access setup wizard
        if ($panel !== 'business') {
            $redirect = redirect()->route("{$panel}.store.dashboard");
        } else {
            $redirect = redirect()->route('business.store.setup.wizard');
        }

        if ($errorMessage) {
            $redirect->with('error', $errorMessage);
        }

        return $redirect;
    }
}
