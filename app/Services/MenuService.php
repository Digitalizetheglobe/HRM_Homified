<?php

namespace App\Services;

use App\Models\DynamicMenu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * Get the dynamic menus authorized for the current user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMenus()
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        // Cache key based on user ID or roles to optimize query performance
        $cacheKey = 'dynamic_menus_user_' . $user->id;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($user) {
            $allMenus = DynamicMenu::with('children')->whereNull('parent_id')->orderBy('order')->get();
            return $this->filterAuthorizedMenus($allMenus, $user);
        });
    }

    /**
     * Recursively filter menus based on user permissions.
     */
    private function filterAuthorizedMenus($menus, $user)
    {
        return $menus->filter(function ($menu) use ($user) {
            // If the menu requires a permission, check it
            if ($menu->permission_required && !$user->can($menu->permission_required)) {
                return false;
            }

            // Recursively filter children
            if ($menu->children->isNotEmpty()) {
                $menu->setRelation('children', $this->filterAuthorizedMenus($menu->children, $user));
            }

            return true;
        });
    }
}
