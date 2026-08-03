<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\SettingService;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create manage_settings permission
        $manageSettingsPermission = Permission::firstOrCreate(['name' => 'manage_settings']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo($manageSettingsPermission);

        // Give super_admin role all existing permissions
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);

        // 2. Create or Update Initial Super Admin User
        $superAdmin = User::updateOrCreate(
            ['email' => 'DOORKNOB@SU'],
            [
                'first_name'  => 'Super',
                'last_name'   => 'Admin',
                'password'    => Hash::make('SU@ADMINDOORKNOB'),
                'gender'      => 'Male',
                'nationality' => 'Indian',
                'phone'       => '0000000000',
                'address'     => 'HQ',
                'address2'    => 'Suite 1',
                'city'        => 'City',
                'zip'         => '000000',
                'role'        => 'super_admin',
            ]
        );

        $superAdmin->assignRole('super_admin');

        // 3. Seed Default Branding & System Settings
        $defaultSettings = [
            // Branding
            ['key' => 'software_name', 'value' => 'DoorKnob', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'software_short_name', 'value' => 'DK', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'tagline', 'value' => 'School Management ERP', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'organization_name', 'value' => 'DoorKnob Education', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'school_name_placeholder', 'value' => 'DoorKnob Academy', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'browser_title', 'value' => 'DoorKnob ERP', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'footer_copyright', 'value' => '© 2026 DoorKnob. All rights reserved.', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'powered_by_text', 'value' => 'Powered by DoorKnob ERP', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'show_powered_by', 'value' => '1', 'group' => 'branding', 'type' => 'boolean'],
            ['key' => 'developer_name', 'value' => 'DoorKnob Systems', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'developer_website', 'value' => 'https://doorknob.io', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'logo', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'dark_logo', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'light_logo', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'login_logo', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'footer_logo', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'favicon', 'value' => null, 'group' => 'branding', 'type' => 'file'],
            ['key' => 'loader_icon', 'value' => null, 'group' => 'branding', 'type' => 'file'],

            // Theme & Styling
            ['key' => 'primary_color', 'value' => '#0d6efd', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'secondary_color', 'value' => '#6c757d', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'accent_color', 'value' => '#ffc107', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'sidebar_color', 'value' => '#ffffff', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'navbar_color', 'value' => '#ffffff', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'background_color', 'value' => '#f8f9fa', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'success_color', 'value' => '#198754', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'danger_color', 'value' => '#dc3545', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'warning_color', 'value' => '#ffc107', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'info_color', 'value' => '#0dcaf0', 'group' => 'theme', 'type' => 'color'],
            ['key' => 'card_radius', 'value' => '8px', 'group' => 'theme', 'type' => 'text'],
            ['key' => 'font_family', 'value' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif", 'group' => 'theme', 'type' => 'text'],
            ['key' => 'google_font_url', 'value' => 'https://fonts.googleapis.com/css?family=Nunito', 'group' => 'theme', 'type' => 'text'],
            ['key' => 'default_theme', 'value' => 'light', 'group' => 'theme', 'type' => 'text'],
            ['key' => 'enable_dark_mode', 'value' => '0', 'group' => 'theme', 'type' => 'boolean'],

            // Contact
            ['key' => 'company_name', 'value' => 'DoorKnob Education Inc.', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'address', 'value' => '100 Innovation Way, Suite 400', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'city', 'value' => 'Tech City', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'state', 'value' => 'CA', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'country', 'value' => 'USA', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'zip_code', 'value' => '90210', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'phone', 'value' => '+1 (800) 555-0199', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'mobile', 'value' => '+1 (800) 555-0199', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'whatsapp', 'value' => '+1 (800) 555-0199', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'email', 'value' => 'support@doorknob.io', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'support_email', 'value' => 'support@doorknob.io', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'admissions_email', 'value' => 'admissions@doorknob.io', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'website', 'value' => 'https://doorknob.io', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'google_maps_url', 'value' => '', 'group' => 'contact', 'type' => 'text'],
            ['key' => 'office_hours', 'value' => 'Mon - Fri: 8:00 AM - 5:00 PM', 'group' => 'contact', 'type' => 'text'],

            // Social Media
            ['key' => 'facebook', 'value' => 'https://facebook.com', 'group' => 'social', 'type' => 'text'],
            ['key' => 'instagram', 'value' => 'https://instagram.com', 'group' => 'social', 'type' => 'text'],
            ['key' => 'twitter', 'value' => 'https://twitter.com', 'group' => 'social', 'type' => 'text'],
            ['key' => 'linkedin', 'value' => 'https://linkedin.com', 'group' => 'social', 'type' => 'text'],
            ['key' => 'youtube', 'value' => 'https://youtube.com', 'group' => 'social', 'type' => 'text'],
            ['key' => 'telegram', 'value' => '', 'group' => 'social', 'type' => 'text'],
            ['key' => 'pinterest', 'value' => '', 'group' => 'social', 'type' => 'text'],
            ['key' => 'github', 'value' => '', 'group' => 'social', 'type' => 'text'],
            ['key' => 'discord', 'value' => '', 'group' => 'social', 'type' => 'text'],
            ['key' => 'whatsapp_channel', 'value' => '', 'group' => 'social', 'type' => 'text'],
            ['key' => 'threads', 'value' => '', 'group' => 'social', 'type' => 'text'],
            ['key' => 'tiktok', 'value' => '', 'group' => 'social', 'type' => 'text'],

            // SEO
            ['key' => 'meta_title', 'value' => 'DoorKnob - White Label Education ERP', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'meta_description', 'value' => 'Next generation school management and white label education ERP software.', 'group' => 'seo', 'type' => 'textarea'],
            ['key' => 'keywords', 'value' => 'school management, ERP, education software, student management', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'canonical_url', 'value' => '', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'robots', 'value' => 'index, follow', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'og_image', 'value' => null, 'group' => 'seo', 'type' => 'file'],
            ['key' => 'twitter_card', 'value' => null, 'group' => 'seo', 'type' => 'file'],
            ['key' => 'google_analytics', 'value' => '', 'group' => 'seo', 'type' => 'textarea'],
            ['key' => 'google_search_console', 'value' => '', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'facebook_pixel', 'value' => '', 'group' => 'seo', 'type' => 'textarea'],
            ['key' => 'clarity_code', 'value' => '', 'group' => 'seo', 'type' => 'textarea'],
            ['key' => 'custom_head_script', 'value' => '', 'group' => 'seo', 'type' => 'textarea'],
            ['key' => 'custom_footer_script', 'value' => '', 'group' => 'seo', 'type' => 'textarea'],

            // Login Page
            ['key' => 'login_bg_image', 'value' => null, 'group' => 'login', 'type' => 'file'],
            ['key' => 'login_welcome_title', 'value' => 'Welcome to DoorKnob ERP', 'group' => 'login', 'type' => 'text'],
            ['key' => 'login_welcome_subtitle', 'value' => 'Please log in with your credentials to access the administrative portal.', 'group' => 'login', 'type' => 'text'],
            ['key' => 'login_button_color', 'value' => '#0d6efd', 'group' => 'login', 'type' => 'color'],
            ['key' => 'login_card_color', 'value' => '#ffffff', 'group' => 'login', 'type' => 'color'],
            ['key' => 'enable_login_animation', 'value' => '1', 'group' => 'login', 'type' => 'boolean'],
            ['key' => 'enable_floating_shapes', 'value' => '1', 'group' => 'login', 'type' => 'boolean'],
            ['key' => 'show_org_name_on_login', 'value' => '1', 'group' => 'login', 'type' => 'boolean'],

            // Email Branding
            ['key' => 'email_logo', 'value' => null, 'group' => 'email', 'type' => 'file'],
            ['key' => 'email_footer', 'value' => 'DoorKnob ERP - School Administration System', 'group' => 'email', 'type' => 'text'],
            ['key' => 'email_signature', 'value' => 'Best regards,<br>DoorKnob ERP Administration', 'group' => 'email', 'type' => 'textarea'],
            ['key' => 'email_support_address', 'value' => 'support@doorknob.io', 'group' => 'email', 'type' => 'text'],
            ['key' => 'email_reply_to', 'value' => 'no-reply@doorknob.io', 'group' => 'email', 'type' => 'text'],

            // Reports & PDF
            ['key' => 'report_header_style', 'value' => 'standard', 'group' => 'reports', 'type' => 'text'],
            ['key' => 'report_footer_text', 'value' => 'This is a computer-generated document. No signature required.', 'group' => 'reports', 'type' => 'text'],
            ['key' => 'show_watermark_on_report', 'value' => '1', 'group' => 'reports', 'type' => 'boolean'],
            ['key' => 'report_primary_color', 'value' => '#0d6efd', 'group' => 'reports', 'type' => 'color'],
            ['key' => 'report_secondary_color', 'value' => '#6c757d', 'group' => 'reports', 'type' => 'color'],

            // System Preferences
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'registration_enabled', 'value' => '0', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'email_verification', 'value' => '0', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'sms_enabled', 'value' => '1', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'whatsapp_enabled', 'value' => '1', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'default_pagination', 'value' => '15', 'group' => 'system', 'type' => 'number'],
            ['key' => 'upload_max_size', 'value' => '10240', 'group' => 'system', 'type' => 'number'],
            ['key' => 'cache_lifetime', 'value' => '86400', 'group' => 'system', 'type' => 'number'],
            ['key' => 'session_timeout', 'value' => '120', 'group' => 'system', 'type' => 'number'],
            ['key' => 'demo_mode', 'value' => '0', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'default_currency', 'value' => 'INR', 'group' => 'system', 'type' => 'text'],
            ['key' => 'currency_symbol', 'value' => '₹', 'group' => 'system', 'type' => 'text'],
            ['key' => 'timezone', 'value' => 'Asia/Kolkata', 'group' => 'system', 'type' => 'text'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'group' => 'system', 'type' => 'text'],
            ['key' => 'time_format', 'value' => 'H:i', 'group' => 'system', 'type' => 'text'],
            ['key' => 'language', 'value' => 'en', 'group' => 'system', 'type' => 'text'],

            // Advanced / Custom Code
            ['key' => 'custom_css', 'value' => '', 'group' => 'advanced', 'type' => 'textarea'],
            ['key' => 'custom_js', 'value' => '', 'group' => 'advanced', 'type' => 'textarea'],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        app(SettingService::class)->clearCache();
    }
}
