<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Business;
use App\Models\BusinessUser;

class DepartmentMiddleware
{
    /**
     * Handle an incoming request.
     * Check if user is a member of the specified department
     */
    public function handle(Request $request, Closure $next, string $department): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $businessId = session('current_business_id');

        if (!$businessId) {
            return redirect('/business')->with('error', 'Biznes tanlanmagan');
        }

        // Check if user is business owner (has access to all departments)
        $business = Business::find($businessId);
        if ($business && $business->user_id === $user->id) {
            return $next($request);
        }

        // Check if user is member of the specified department
        $membership = BusinessUser::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('department', $department)
            ->first();

        if (!$membership) {
            $departmentNames = [
                'marketing' => 'marketing bo\'limi',
                'finance' => 'moliya bo\'limi',
                'operator' => 'operator',
                'sales_head' => 'sotuv boshlig\'i',
            ];

            $deptName = $departmentNames[$department] ?? $department;
            return redirect('/business')->with('error', "Sizda {$deptName} huquqlari yo'q");
        }

        return $next($request);
    }
}
