<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\BusinessUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentMiddleware
{
    /**
     * Handle an incoming request.
     * Check if user is a member of the specified department or has the appropriate role
     */
    public function handle(Request $request, Closure $next, string $department): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user has the appropriate Spatie role (for role-based access without team membership)
        $roleMap = [
            'operator' => ['operator', 'sales_operator'],
            'sales_head' => ['sales_head'],
            'marketing' => ['marketing'],
            'finance' => ['finance'],
            'hr' => ['hr'],
        ];

        $allowedRoles = $roleMap[$department] ?? [];
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                // User has role - allow access even without business context
                return $next($request);
            }
        }

        // For team members, check business context
        $businessId = session('current_business_id');

        if (! $businessId) {
            $departmentNames = [
                'marketing' => 'Marketing bo\'limi',
                'finance' => 'Moliya bo\'limi',
                'operator' => 'Operator',
                'sales_head' => 'Sotuv boshlig\'i',
                'hr' => 'HR bo\'limi',
            ];

            $deptName = $departmentNames[$department] ?? $department;

            return redirect()->route('business.dashboard')->with('error', "Biznes tanlanmagan. {$deptName} panelidan foydalanish uchun biznesni tanlang.");
        }

        // Check if user is business owner (has access to all departments)
        $business = Business::find($businessId);
        if ($business && $business->user_id === $user->id) {
            return $next($request);
        }

        // Check if user is member of the specified department
        $membership = BusinessUser::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where(function ($query) use ($department) {
                $query->where('department', $department);
                // Allow 'sales_operator' department to access 'operator' routes
                if ($department === 'operator') {
                    $query->orWhere('department', 'sales_operator');
                }
            })
            ->first();

        if (! $membership) {
            $departmentNames = [
                'marketing' => 'Marketing bo\'limi',
                'finance' => 'Moliya bo\'limi',
                'operator' => 'Operator',
                'sales_head' => 'Sotuv boshlig\'i',
                'hr' => 'HR bo\'limi',
            ];

            $deptName = $departmentNames[$department] ?? $department;

            return redirect()->route('business.dashboard')->with('error', "Sizda {$deptName} huquqlari yo'q");
        }

        return $next($request);
    }
}
