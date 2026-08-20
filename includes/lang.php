<?php
// ============================================================
// includes/lang.php
// Multilingual Translation Support (English, Chinese, Malay)
// ============================================================

/**
 * Get list of supported languages with metadata.
 */
function getAvailableLanguages(): array {
    return [
        'en' => [
            'code'   => 'en',
            'name'   => 'English',
            'native' => 'English',
            'flag'   => '🇬🇧',
        ],
        'zh' => [
            'code'   => 'zh',
            'name'   => 'Chinese',
            'native' => '中文',
            'flag'   => '🇨🇳',
        ],
        'ms' => [
            'code'   => 'ms',
            'name'   => 'Malay',
            'native' => 'Bahasa Melayu',
            'flag'   => '🇲🇾',
        ],
    ];
}

/**
 * Set and persist the application language in session and cookie.
 */
function setAppLanguage(string $lang): string {
    $available = getAvailableLanguages();
    if (!array_key_exists($lang, $available)) {
        $lang = 'en';
    }
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('SESSION_NAME')) {
            session_name(SESSION_NAME);
        }
        session_start();
    }
    $_SESSION['app_lang'] = $lang;
    setcookie('app_lang', $lang, [
        'expires'  => time() + (86400 * 365),
        'path'     => '/',
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
    return $lang;
}

/**
 * Get currently active language code.
 */
function getCurrentLang(): string {
    if (isset($_GET['lang'])) {
        $l = trim($_GET['lang']);
        $available = getAvailableLanguages();
        if (array_key_exists($l, $available)) {
            return setAppLanguage($l);
        }
    }
    if (isset($_SESSION['app_lang'])) {
        return $_SESSION['app_lang'];
    }
    if (isset($_COOKIE['app_lang'])) {
        $c = $_COOKIE['app_lang'];
        $available = getAvailableLanguages();
        if (array_key_exists($c, $available)) {
            $_SESSION['app_lang'] = $c;
            return $c;
        }
    }
    return 'en';
}

/**
 * Returns translation dictionary array.
 */
function getTranslations(): array {
    static $dict = null;
    if ($dict !== null) {
        return $dict;
    }

    $dict = [
        // ============================================================
        // ENGLISH (en) - Default
        // ============================================================
        'en' => [
            // App info
            'app_name'              => 'ToiletTrack',
            'app_subtitle'          => 'Cleanliness Monitoring System',
            'college_name'          => 'College Cleanliness Monitoring',

            // Navigation
            'nav_dashboard'         => 'Dashboard',
            'nav_users'             => 'Users',
            'nav_toilets'           => 'Toilets',
            'nav_history'           => 'History',
            'nav_my_toilets'        => 'My Toilets',
            'nav_profile_picture'   => 'Profile Picture',
            'nav_change_password'   => 'Change Password',
            'nav_logout'            => 'Logout',
            'nav_language'          => 'Language',

            // Roles & badges
            'role_admin'            => 'Admin',
            'role_student'          => 'Student',
            'status_active'         => 'Active',
            'status_inactive'       => 'Inactive',
            'status_open'           => 'Open',
            'status_closed'         => 'Closed',
            'badge_active_checkin'  => 'Active Check-In',
            'badge_ready'           => 'Ready to Check In',
            'badge_ongoing'         => 'Ongoing',
            'badge_none_assigned'   => 'None assigned',
            'badge_custom_photo'    => 'Custom Photo',
            'badge_default_initial' => 'Default Initial',
            'badge_new_preview'     => 'New Preview',

            // General Actions & Buttons
            'action_add'            => 'Add',
            'action_edit'           => 'Edit',
            'action_delete'         => 'Delete',
            'action_save'           => 'Save',
            'action_save_changes'   => 'Save Changes',
            'action_cancel'         => 'Cancel',
            'action_submit'         => 'Submit',
            'action_search'         => 'Search',
            'action_clear'          => 'Clear',
            'action_filter'         => 'Filter',
            'action_close'          => 'Close',
            'action_view_all'       => 'View All',
            'action_view_details'   => 'View Details',
            'action_view_history'   => 'View History',
            'action_view_toilet'    => 'View Toilet',
            'action_back_to_login'  => 'Back to Login',
            'action_sign_in'        => 'Sign In',
            'action_remove'         => 'Remove',

            // Login Page
            'login_title'           => 'Sign In',
            'login_feature_camera'  => 'Photo Evidence Check-In / Check-Out',
            'login_feature_history' => 'Complete Cleanliness History',
            'login_feature_rbac'    => 'Role-Based Access Control',
            'login_username_label'  => 'Username',
            'login_password_label'  => 'Password',
            'login_username_placeholder' => 'Enter your username',
            'login_password_placeholder' => 'Enter your password',
            'login_error_empty'     => 'Please enter your username and password.',
            'login_error_invalid'   => 'Invalid username or password. Please try again.',
            'db_not_setup_title'    => 'Database not set up yet!',
            'db_not_setup_desc'     => 'Please run the setup first before logging in.',
            'db_run_setup_now'      => 'Run Setup Now',

            // Change Password Page
            'cp_title'              => 'Change Password',
            'cp_subtitle_must'      => 'Welcome! Please set your own password before continuing.',
            'cp_subtitle_normal'    => 'Update your account password.',
            'cp_alert_must'         => 'You are required to set a new password before accessing the system.',
            'cp_card_title'         => 'Password Settings',
            'cp_current_password'   => 'Current Password',
            'cp_current_placeholder'=> 'Enter current password',
            'cp_new_password'       => 'New Password',
            'cp_new_placeholder'    => 'Minimum 8 characters',
            'cp_hint'               => 'At least 8 characters. Use a mix of letters and numbers.',
            'cp_confirm_password'   => 'Confirm New Password',
            'cp_confirm_placeholder'=> 'Re-enter new password',
            'cp_submit_btn'         => 'Save Password',
            'cp_error_current'      => 'Current password is incorrect.',
            'cp_error_length'       => 'New password must be at least 8 characters long.',
            'cp_error_match'        => 'Passwords do not match.',
            'cp_success'            => 'Password changed successfully!',

            // User Dashboard (My Toilets)
            'user_dash_title'       => 'My Assigned Toilets',
            'user_dash_welcome'     => 'Welcome back, %s! Select a toilet to check in.',
            'user_dash_assigned_count' => 'You have %d toilet(s) assigned. Click a toilet to check in.',
            'user_dash_empty_title' => 'No Toilets Assigned',
            'user_dash_empty_desc'  => 'You have not been assigned to any toilet yet.<br>Please contact your administrator.',
            'user_dash_since'       => 'since %s',

            // Toilet Check-In / Check-Out Page
            'toilet_page_title'     => '%s — Check In/Out',
            'active_session_title'  => 'Active Session — %s',
            'checked_in_at'         => 'Checked In At',
            'checkin_note'          => 'Check-In Note',
            'before_photos'         => 'Before Photos',
            'after_photos'          => 'After Photos',
            'photos_uploaded'       => '%d uploaded',
            'checkout_title'        => 'Check Out — Upload After Photos',
            'after_photos_label'    => 'After Cleaning Photos',
            'checkout_comment_label'=> 'Comment / Remarks (Optional)',
            'checkout_comment_ph'   => 'e.g. Floor cleaned, rubbish removed, toilet flushed…',
            'submit_checkout_btn'   => 'Submit Check-Out',
            'confirm_checkout_msg'  => 'Confirm check-out for %s?',
            'checkin_title'         => 'Check In — %s',
            'checkin_alert_info'    => 'Take photos of the toilet <strong>before</strong> cleaning. The check-in time will be recorded automatically.',
            'before_photos_label'   => 'Before Photos (Current Condition)',
            'checkin_comment_label' => 'Comment / Observations (Optional)',
            'checkin_comment_ph'    => 'e.g. Floor wet, rubbish bin full, no soap…',
            'submit_checkin_btn'    => 'Submit Check-In',
            'confirm_checkin_msg'   => 'Confirm check-in for %s?',
            'take_photo_live'       => 'Take Photo (Live Camera)',
            'camera_hint'           => 'Live camera capture only • Tap anywhere to launch camera',
            'open_camera_btn'       => 'Open Live Camera',
            'toilet_history_title'  => 'Toilet History',
            'records_count'         => '%d record(s)',
            'no_history_title'      => 'No History Yet',
            'no_history_desc'       => 'Completed sessions will appear here.',
            'no_comment'            => 'No comment',
            'none'                  => 'None',
            'duration_label'        => 'Duration',
            'flash_not_assigned'    => 'You are not assigned to this toilet.',
            'flash_toilet_not_found'=> 'Toilet not found or inactive.',
            'flash_already_active'  => 'You already have an active check-in for this toilet. Please check out first.',
            'flash_checkin_success' => 'Check-in recorded successfully! Remember to check out when done.',
            'flash_no_active_sess'  => 'No active check-in found. You must check in before checking out.',
            'flash_checkout_success'=> 'Check-out completed! Session recorded successfully.',

            // User Profile Picture
            'profile_title'         => 'Profile Picture & Account',
            'profile_subtitle'      => 'Customize your personal profile picture and manage your account details.',
            'profile_custom_pic'    => 'Custom Profile Picture',
            'profile_pic_desc'      => 'Upload a clear photo or take a selfie using your camera.<br><small>Supported formats: JPG, PNG, WEBP, GIF (Max 5MB)</small>',
            'profile_choose_img'    => 'Choose Image',
            'profile_take_photo'    => 'Take Photo',
            'profile_save_btn'      => 'Save Profile Picture',
            'profile_remove_btn'    => 'Remove Profile Picture',
            'profile_remove_confirm'=> 'Remove custom profile picture and revert to initial avatar?',
            'profile_acc_details'   => 'Account Details',
            'profile_fullname'      => 'Full Name',
            'profile_username'      => 'Username',
            'profile_email'         => 'Email Address',
            'profile_role'          => 'Role',
            'profile_member_since'  => 'Member Since',
            'profile_assigned_toilets' => 'Assigned Toilets (%d)',
            'profile_no_toilets'    => 'No toilets currently assigned.',
            'profile_camera_title'  => 'Profile Photo Camera',
            'profile_camera_denied' => 'Camera access denied. Please allow camera permission in your browser.',
            'profile_update_success'=> 'Profile picture updated successfully!',
            'profile_remove_success'=> 'Profile picture removed. Reverted to default avatar.',
            'profile_select_photo'  => 'Please select or take a photo first.',
            'profile_size_error'    => 'Image size must be 5MB or less.',
            'profile_format_error'  => 'Invalid file format. Please upload JPG, PNG, WEBP, or GIF.',

            // Admin Dashboard
            'admin_dash_title'      => 'Admin Dashboard',
            'stat_total_users'      => 'Total Users',
            'stat_total_toilets'    => 'Total Toilets',
            'stat_active_checkins'  => 'Active Check-Ins',
            'stat_today_checkins'   => 'Today\'s Check-Ins',
            'stat_today_checkouts'  => 'Today\'s Check-Outs',
            'live_active_sessions'  => 'Live Active Sessions',
            'all_clear_title'       => 'All Clear',
            'all_clear_desc'        => 'No active check-ins at the moment.',
            'th_toilet'             => 'Toilet',
            'th_user'               => 'User',
            'th_since'              => 'Since',
            'th_elapsed'            => 'Elapsed',
            'th_checkin'            => 'Check In',
            'th_checkout'           => 'Check Out',
            'th_duration'           => 'Duration',
            'th_status'             => 'Status',
            'th_actions'            => 'Actions',
            'quick_actions'         => 'Quick Actions',
            'manage_users'          => 'Manage Users',
            'manage_toilets'        => 'Manage Toilets',
            'view_full_history'     => 'View Full History',
            'add_new_user'          => 'Add New User',
            'add_new_toilet'        => 'Add New Toilet',
            'recent_sessions'       => 'Recent Sessions',
            'no_sessions_title'     => 'No Sessions Yet',
            'no_sessions_desc'      => 'Check-in records will appear here once users start their sessions.',

            // Admin Users Management
            'users_mgmt_title'      => 'User Management',
            'users_mgmt_subtitle'   => 'Create and manage student accounts and toilet assignments.',
            'search_users_ph'       => 'Search users…',
            'th_name'               => 'Name',
            'th_username'           => 'Username',
            'th_email'              => 'Email',
            'th_role'               => 'Role',
            'th_toilets'            => 'Toilets',
            'no_users_found'        => 'No Users Found',
            'no_users_desc'         => 'Add your first user to get started.',
            'modal_add_user'        => 'Add New User',
            'modal_edit_user'       => 'Edit User',
            'form_username_req'     => 'Username *',
            'form_fullname_req'     => 'Full Name *',
            'form_email'            => 'Email',
            'form_role'             => 'Role',
            'form_temp_pass_req'    => 'Temporary Password *',
            'form_temp_pass_hint'   => 'The user will be required to change this on first login.',
            'form_assign_toilets'   => 'Assign Toilets',
            'form_no_toilets_yet'   => 'No toilets created yet. Add one first.',
            'btn_create_user'       => 'Create User',
            'form_username_fixed'   => 'Username cannot be changed.',
            'form_reset_pass'       => 'Reset Password',
            'form_reset_pass_opt'   => '(leave blank to keep)',
            'form_reset_pass_hint'  => 'If set, user will be forced to change on next login.',
            'confirm_delete_user'   => 'Delete user \'%s\'? This will also remove all their session history.',
            'flash_user_created'    => 'User \'%s\' created successfully.',
            'flash_user_updated'    => 'User updated successfully.',
            'flash_user_deleted'    => 'User deleted successfully.',
            'flash_user_self_del'   => 'You cannot delete your own account.',
            'flash_username_taken'  => 'Username \'%s\' is already taken.',
            'flash_user_req_fields' => 'Username, full name and temporary password are required.',

            // Admin Toilets Management
            'toilets_mgmt_title'    => 'Toilet Management',
            'toilets_mgmt_subtitle' => 'Add, edit and manage all monitored toilets.',
            'no_toilets_title'      => 'No Toilets Yet',
            'no_toilets_desc'       => 'Add your first toilet to start monitoring cleanliness.',
            'th_location'           => 'Location',
            'th_assigned_users'     => 'Assigned Users',
            'th_total_sessions'     => 'Total Sessions',
            'th_active'             => 'Active',
            'modal_add_toilet'      => 'Add New Toilet',
            'modal_edit_toilet'     => 'Edit Toilet',
            'form_toilet_name_req'  => 'Toilet Name / Number *',
            'form_toilet_name_ph'   => 'e.g. T01, Block A Male, Ground Floor Female',
            'form_toilet_loc_ph'    => 'e.g. Block A, Ground Floor',
            'form_toilet_desc_ph'   => 'Additional notes about this toilet…',
            'confirm_delete_toilet' => 'Delete toilet \'%s\'? All session history will be lost.',
            'flash_toilet_added'    => 'Toilet \'%s\' added successfully.',
            'flash_toilet_updated'  => 'Toilet updated successfully.',
            'flash_toilet_deleted'  => 'Toilet deleted successfully.',
            'flash_toilet_del_err'  => 'Cannot delete: this toilet has active check-in sessions.',
            'flash_toilet_name_req' => 'Toilet name is required.',

            // Admin History
            'history_mgmt_title'    => 'Session History',
            'history_mgmt_subtitle' => 'Complete check-in / check-out records with photo evidence.',
            'history_title'         => 'Session History',
            'history_subtitle'      => 'Complete check-in / check-out records with photo evidence.',
            'filter_all_toilets'    => 'All Toilets',
            'filter_all_students'   => 'All Students',
            'filter_all_status'     => 'All Status',
            'filter_by_date'        => 'Filter by date',
            'filter_records_found'  => '%d record(s) found',
            'records_found'         => '%d record(s) found',
            'delete_all_visible'    => 'Delete All Visible (%d)',
            'confirm_delete_all'    => 'Delete ALL %d visible record(s)? This cannot be undone.',
            'confirm_delete_all_filtered' => 'Delete ALL %d visible record(s)? This cannot be undone.',
            'confirm_delete_sess'   => 'Delete this session record for %s? This cannot be undone.',
            'confirm_delete_session'=> 'Delete this session record for %s? This cannot be undone.',
            'flash_session_deleted' => 'Session record deleted.',
            'flash_sessions_deleted'=> '%d session record(s) deleted.',
            'flash_history_deleted' => 'Session record deleted.',
            'flash_all_history_deleted' => '%d session record(s) deleted.',
            'session_in_progress'   => 'Session In Progress',
            'session_in_prog_desc'  => 'User has checked in but not yet checked out.',
            'session_in_progress_hint' => 'User has checked in but not yet checked out.',
            'no_history_found'      => 'No History Found',
            'no_photos_uploaded'    => 'No photos uploaded',
            'photos_count'          => '%d photo(s)',
            'check_in_title'        => 'Check-In',
            'check_out_title'       => 'Check-Out',
            'before_photos_count'   => 'Before Photos (%d)',
            'after_photos_count'    => 'After Photos (%d)',

            // Camera modal component
            'camera_modal_title'    => 'Live Camera Capture',
            'camera_placeholder'    => 'Camera will appear here',
            'camera_permission_hint'=> 'Allow camera permission when prompted',
            'camera_use_photos'     => 'Use %d Photo(s)',
            'camera_captured_label' => 'Captured Photos',
            'flip_camera'           => 'Flip Camera',
            'take_photo_shutter'    => 'Take Photo',

            // Setup
            'setup_title'           => 'ToiletTrack Setup',
            'setup_subtitle'        => 'Database Initializer',
            'setup_warn'            => 'This will create the database schema and seed a default admin account.<br>Make sure your database credentials in <code>config/database.php</code> are correct.',
            'setup_warning'         => '⚠️ This will create the database schema and seed a default admin account.<br>Make sure your database credentials in <code>config/database.php</code> are correct.',
            'setup_run_btn'         => 'Run Setup',
            'setup_tables_ok'       => 'Database tables created successfully.',
            'setup_admin_seeded'    => 'Default admin account seeded: <strong>username: admin / password: Admin@123</strong>',
            'setup_delete_warn'     => 'Please delete <code>setup.php</code> after setup is complete.',
            'setup_success_tables'  => '✅ Database tables created successfully.',
            'setup_success_admin'   => '✅ Default admin account seeded: <strong>username: admin / password: Admin@123</strong>',
            'setup_delete_notice'   => '🔒 Please delete <code>setup.php</code> after setup is complete.',
            'setup_go_login'        => 'Go to Login',
            'setup_back_login'      => 'Back to Login',
            'setup_goto_login'      => 'Go to Login →',
        ],

        // ============================================================
        // CHINESE (zh) - 简体中文
        // ============================================================
        'zh' => [
            // App info
            'app_name'              => 'ToiletTrack',
            'app_subtitle'          => '卫生间清洁监测系统',
            'college_name'          => '学院卫生间清洁监测',

            // Navigation
            'nav_dashboard'         => '仪表板',
            'nav_users'             => '用户管理',
            'nav_toilets'           => '卫生间列表',
            'nav_history'           => '打卡记录',
            'nav_my_toilets'        => '我的卫生间',
            'nav_profile_picture'   => '个人头像',
            'nav_change_password'   => '修改密码',
            'nav_logout'            => '登出',
            'nav_language'          => '语言',

            // Roles & badges
            'role_admin'            => '管理员',
            'role_student'          => '学生',
            'status_active'         => '活跃',
            'status_inactive'       => '停用',
            'status_open'           => '进行中',
            'status_closed'         => '已完成',
            'badge_active_checkin'  => '进行中签到',
            'badge_ready'           => '准备签到',
            'badge_ongoing'         => '进行中',
            'badge_none_assigned'   => '未分配',
            'badge_custom_photo'    => '自定义头像',
            'badge_default_initial' => '默认首字母',
            'badge_new_preview'     => '新预览',

            // General Actions & Buttons
            'action_add'            => '添加',
            'action_edit'           => '编辑',
            'action_delete'         => '删除',
            'action_save'           => '保存',
            'action_save_changes'   => '保存更改',
            'action_cancel'         => '取消',
            'action_submit'         => '提交',
            'action_search'         => '搜索',
            'action_clear'          => '清除',
            'action_filter'         => '筛选',
            'action_close'          => '关闭',
            'action_view_all'       => '查看全部',
            'action_view_details'   => '查看详情',
            'action_view_history'   => '查看历史',
            'action_view_toilet'    => '查看卫生间',
            'action_back_to_login'  => '返回登录',
            'action_sign_in'        => '登录',
            'action_remove'         => '移除',

            // Login Page
            'login_title'           => '登录',
            'login_feature_camera'  => '拍照取证签到 / 签退',
            'login_feature_history' => '完整清洁历史记录',
            'login_feature_rbac'    => '基于角色的权限控制',
            'login_username_label'  => '用户名',
            'login_password_label'  => '密码',
            'login_username_placeholder' => '请输入用户名',
            'login_password_placeholder' => '请输入密码',
            'login_error_empty'     => '请输入您的用户名和密码。',
            'login_error_invalid'   => '用户名或密码错误，请重试。',
            'db_not_setup_title'    => '数据库尚未配置！',
            'db_not_setup_desc'     => '请在登录前先运行初始化设置。',
            'db_run_setup_now'      => '立即运行设置',

            // Change Password Page
            'cp_title'              => '修改密码',
            'cp_subtitle_must'      => '欢迎！请先设置您的新密码后再继续。',
            'cp_subtitle_normal'    => '更新您的账户密码。',
            'cp_alert_must'         => '在访问系统之前，您必须先设置新密码。',
            'cp_card_title'         => '密码设置',
            'cp_current_password'   => '当前密码',
            'cp_current_placeholder'=> '输入当前密码',
            'cp_new_password'       => '新密码',
            'cp_new_placeholder'    => '至少 8 个字符',
            'cp_hint'               => '至少 8 个字符，建议结合字母和数字。',
            'cp_confirm_password'   => '确认新密码',
            'cp_confirm_placeholder'=> '再次输入新密码',
            'cp_submit_btn'         => '保存密码',
            'cp_error_current'      => '当前密码不正确。',
            'cp_error_length'       => '新密码长度必须至少为 8 个字符。',
            'cp_error_match'        => '两次输入的密码不一致。',
            'cp_success'            => '密码修改成功！',

            // User Dashboard (My Toilets)
            'user_dash_title'       => '我负责的卫生间',
            'user_dash_welcome'     => '欢迎回来，%s！请选择卫生间进行打卡。',
            'user_dash_assigned_count' => '您共分配到 %d 个卫生间。点击卡片进行签到。',
            'user_dash_empty_title' => '未分配卫生间',
            'user_dash_empty_desc'  => '您目前尚未分配到任何卫生间。<br>请联系管理员。',
            'user_dash_since'       => '自 %s 开始',

            // Toilet Check-In / Check-Out Page
            'toilet_page_title'     => '%s — 签到 / 签退',
            'active_session_title'  => '进行中的打卡 — %s',
            'checked_in_at'         => '签到时间',
            'checkin_note'          => '签到备注',
            'before_photos'         => '清洁前照片',
            'after_photos'          => '清洁后照片',
            'photos_uploaded'       => '已上传 %d 张',
            'checkout_title'        => '签退 — 上传清洁后照片',
            'after_photos_label'    => '清洁后现场照片',
            'checkout_comment_label'=> '备注 / 说明（选填）',
            'checkout_comment_ph'   => '例如：已拖地、倒垃圾、冲洗马桶…',
            'submit_checkout_btn'   => '提交签退',
            'confirm_checkout_msg'  => '确认完成 %s 的签退？',
            'checkin_title'         => '签到 — %s',
            'checkin_alert_info'    => '请在清洁<strong>之前</strong>拍摄卫生间状况。签到时间将自动记录。',
            'before_photos_label'   => '清洁前照片（现场初始状况）',
            'checkin_comment_label' => '观察 / 备注（选填）',
            'checkin_comment_ph'    => '例如：地面潮湿、垃圾桶满、无洗手液…',
            'submit_checkin_btn'    => '提交签到',
            'confirm_checkin_msg'   => '确认签到 %s？',
            'take_photo_live'       => '实时拍照（打开相机）',
            'camera_hint'           => '仅支持实时相机拍摄 • 点击任意区域开启相机',
            'open_camera_btn'       => '开启实时相机',
            'toilet_history_title'  => '卫生间历史记录',
            'records_count'         => '%d 条记录',
            'no_history_title'      => '暂无历史记录',
            'no_history_desc'       => '完成的清洁记录将显示在此处。',
            'no_comment'            => '无备注',
            'none'                  => '无',
            'duration_label'        => '时长',
            'flash_not_assigned'    => '您未被分配到该卫生间。',
            'flash_toilet_not_found'=> '卫生间不存在或已停用。',
            'flash_already_active'  => '该卫生间已有进行中的签到，请先完成签退。',
            'flash_checkin_success' => '签到记录成功！完成后请记得签退。',
            'flash_no_active_sess'  => '未找到活跃的签到记录，请先签到后再签退。',
            'flash_checkout_success'=> '签退已完成！打卡记录保存成功。',

            // User Profile Picture
            'profile_title'         => '个人头像与账户',
            'profile_subtitle'      => '自定义您的个人头像并管理账户信息。',
            'profile_custom_pic'    => '自定义头像',
            'profile_pic_desc'      => '上传清晰照片或使用相机自拍。<br><small>支持格式：JPG, PNG, WEBP, GIF（最大 5MB）</small>',
            'profile_choose_img'    => '选择图片',
            'profile_take_photo'    => '拍照自拍',
            'profile_save_btn'      => '保存个人头像',
            'profile_remove_btn'    => '移除个人头像',
            'profile_remove_confirm'=> '确认移除自定义头像并恢复默认首字母头像？',
            'profile_acc_details'   => '账户信息',
            'profile_fullname'      => '全名',
            'profile_username'      => '用户名',
            'profile_email'         => '电子邮箱',
            'profile_role'          => '角色',
            'profile_member_since'  => '注册时间',
            'profile_assigned_toilets' => '分配的卫生间 (%d)',
            'profile_no_toilets'    => '当前尚未分配卫生间。',
            'profile_camera_title'  => '头像自拍相机',
            'profile_camera_denied' => '相机访问被拒绝，请在浏览器设置中允许相机权限。',
            'profile_update_success'=> '个人头像更新成功！',
            'profile_remove_success'=> '个人头像已移除，已恢复默认头像。',
            'profile_select_photo'  => '请先选择或拍摄一张照片。',
            'profile_size_error'    => '图片大小必须在 5MB 以内。',
            'profile_format_error'  => '无效的文件格式，请上传 JPG, PNG, WEBP 或 GIF。',

            // Admin Dashboard
            'admin_dash_title'      => '管理仪表板',
            'stat_total_users'      => '总用户数',
            'stat_total_toilets'    => '总卫生间数',
            'stat_active_checkins'  => '进行中打卡',
            'stat_today_checkins'   => '今日签到数',
            'stat_today_checkouts'  => '今日签退数',
            'live_active_sessions'  => '实时进行中的打卡',
            'all_clear_title'       => '当前无进行中打卡',
            'all_clear_desc'        => '目前所有卫生间均未处于清洁打卡状态。',
            'th_toilet'             => '卫生间',
            'th_user'               => '用户',
            'th_since'              => '签到时间',
            'th_elapsed'            => '已进行时长',
            'th_checkin'            => '签到',
            'th_checkout'           => '签退',
            'th_duration'           => '耗时',
            'th_status'             => '状态',
            'th_actions'            => '操作',
            'quick_actions'         => '快捷操作',
            'manage_users'          => '管理用户',
            'manage_toilets'        => '管理卫生间',
            'view_full_history'     => '查看完整历史',
            'add_new_user'          => '添加新用户',
            'add_new_toilet'        => '添加新卫生间',
            'recent_sessions'       => '最近打卡记录',
            'no_sessions_title'     => '暂无打卡记录',
            'no_sessions_desc'      => '当用户开始打卡后，记录将显示在此处。',

            // Admin Users Management
            'users_mgmt_title'      => '用户管理',
            'users_mgmt_subtitle'   => '创建和管理学生账户及卫生间分配。',
            'search_users_ph'       => '搜索用户…',
            'th_name'               => '姓名',
            'th_username'           => '用户名',
            'th_email'              => '邮箱',
            'th_role'               => '角色',
            'th_toilets'            => '卫生间',
            'no_users_found'        => '未找到用户',
            'no_users_desc'         => '请添加第一个用户以开始。',
            'modal_add_user'        => '添加新用户',
            'modal_edit_user'       => '编辑用户',
            'form_username_req'     => '用户名 *',
            'form_fullname_req'     => '全名 *',
            'form_email'            => '电子邮箱',
            'form_role'             => '角色',
            'form_temp_pass_req'    => '临时密码 *',
            'form_temp_pass_hint'   => '用户首次登录时必须更改此密码。',
            'form_assign_toilets'   => '分配卫生间',
            'form_no_toilets_yet'   => '尚未创建卫生间，请先添加卫生间。',
            'btn_create_user'       => '创建用户',
            'form_username_fixed'   => '用户名不可更改。',
            'form_reset_pass'       => '重置密码',
            'form_reset_pass_opt'   => '（留空则保持不变）',
            'form_reset_pass_hint'  => '若设置，用户下次登录时将被强制修改密码。',
            'confirm_delete_user'   => '确定要删除用户 \'%s\' 吗？这将同时删除该用户的所有打卡记录。',
            'flash_user_created'    => '用户 \'%s\' 创建成功。',
            'flash_user_updated'    => '用户信息更新成功。',
            'flash_user_deleted'    => '用户删除成功。',
            'flash_user_self_del'   => '您不能删除自己的账户。',
            'flash_username_taken'  => '用户名 \'%s\' 已被占用。',
            'flash_user_req_fields' => '用户名、全名和临时密码为必填项。',

            // Admin Toilets Management
            'toilets_mgmt_title'    => '卫生间管理',
            'toilets_mgmt_subtitle' => '添加、编辑和管理所有受监测的卫生间。',
            'no_toilets_title'      => '暂无卫生间',
            'no_toilets_desc'       => '添加第一个卫生间以开始监测清洁状况。',
            'th_location'           => '位置',
            'th_assigned_users'     => '分配的用户',
            'th_total_sessions'     => '总打卡次数',
            'th_active'             => '进行中',
            'modal_add_toilet'      => '添加新卫生间',
            'modal_edit_toilet'     => '编辑卫生间',
            'form_toilet_name_req'  => '卫生间名称 / 编号 *',
            'form_toilet_name_ph'   => '例如：T01、A栋男厕、一楼女厕',
            'form_toilet_loc_ph'    => '例如：A栋一楼',
            'form_toilet_desc_ph'   => '有关该卫生间的附加说明…',
            'confirm_delete_toilet' => '确定要删除卫生间 \'%s\' 吗？所有相关的打卡历史将丢失。',
            'flash_toilet_added'    => '卫生间 \'%s\' 添加成功。',
            'flash_toilet_updated'  => '卫生间信息更新成功。',
            'flash_toilet_deleted'  => '卫生间删除成功。',
            'flash_toilet_del_err'  => '无法删除：该卫生间存在进行中的打卡记录。',
            'flash_toilet_name_req' => '卫生间名称为必填项。',

            // Admin History
            'history_mgmt_title'    => '打卡历史记录',
            'history_mgmt_subtitle' => '带有照片凭证的完整签到 / 签退记录。',
            'history_title'         => '打卡历史记录',
            'history_subtitle'      => '带有照片凭证的完整签到 / 签退记录。',
            'filter_all_toilets'    => '所有卫生间',
            'filter_all_students'   => '所有学生',
            'filter_all_status'     => '所有状态',
            'filter_by_date'        => '按日期筛选',
            'filter_records_found'  => '找到 %d 条记录',
            'records_found'         => '找到 %d 条记录',
            'delete_all_visible'    => '删除所有当前可见记录 (%d)',
            'confirm_delete_all'    => '确定删除当前可见的全部 %d 条记录吗？此操作无法撤销。',
            'confirm_delete_all_filtered' => '确定删除当前可见的全部 %d 条记录吗？此操作无法撤销。',
            'confirm_delete_sess'   => '确定删除 %s 的这条打卡记录吗？此操作无法撤销。',
            'confirm_delete_session'=> '确定删除 %s 的这条打卡记录吗？此操作无法撤销。',
            'flash_session_deleted' => '打卡记录已删除。',
            'flash_sessions_deleted'=> '已删除 %d 条打卡记录。',
            'flash_history_deleted' => '打卡记录已删除。',
            'flash_all_history_deleted' => '已删除 %d 条打卡记录。',
            'session_in_progress'   => '清洁进行中',
            'session_in_prog_desc'  => '用户已签到，尚未签退。',
            'session_in_progress_hint' => '用户已签到，尚未签退。',
            'no_history_found'      => '未找到打卡记录',
            'no_photos_uploaded'    => '未上传照片',
            'photos_count'          => '%d 张照片',
            'check_in_title'        => '签到',
            'check_out_title'       => '签退',
            'before_photos_count'   => '清洁前照片 (%d)',
            'after_photos_count'    => '清洁后照片 (%d)',

            // Camera modal component
            'camera_modal_title'    => '实时相机拍摄',
            'camera_placeholder'    => '相机画面将在此显示',
            'camera_permission_hint'=> '请在提示时允许相机权限',
            'camera_use_photos'     => '使用 %d 张照片',
            'camera_captured_label' => '已拍摄照片',
            'flip_camera'           => '切换摄像头',
            'take_photo_shutter'    => '拍照',

            // Setup
            'setup_title'           => 'ToiletTrack 系统设置',
            'setup_subtitle'        => '数据库初始化工具',
            'setup_warn'            => '此操作将创建数据库表并生成默认管理员账户。<br>请确保 <code>config/database.php</code> 中的数据库连接配置正确。',
            'setup_warning'         => '⚠️ 此操作将创建数据库表并生成默认管理员账户。<br>请确保 <code>config/database.php</code> 中的数据库连接配置正确。',
            'setup_run_btn'         => '开始初始化',
            'setup_tables_ok'       => '数据库表创建成功。',
            'setup_admin_seeded'    => '已生成默认管理员账户：<strong>用户名: admin / 密码: Admin@123</strong>',
            'setup_delete_warn'     => '设置完成后，请删除 <code>setup.php</code> 文件以确保安全。',
            'setup_success_tables'  => '✅ 数据库表创建成功。',
            'setup_success_admin'   => '✅ 已生成默认管理员账户：<strong>用户名: admin / 密码: Admin@123</strong>',
            'setup_delete_notice'   => '🔒 设置完成后，请删除 <code>setup.php</code> 文件以确保安全。',
            'setup_go_login'        => '前往登录页面',
            'setup_back_login'      => '返回登录页面',
            'setup_goto_login'      => '前往登录页面 →',
        ],

        // ============================================================
        // MALAY (ms) - Bahasa Melayu
        // ============================================================
        'ms' => [
            // App info
            'app_name'              => 'ToiletTrack',
            'app_subtitle'          => 'Sistem Pemantauan Kebersihan',
            'college_name'          => 'Pemantauan Kebersihan Kolej',

            // Navigation
            'nav_dashboard'         => 'Papan Pemuka',
            'nav_users'             => 'Pengguna',
            'nav_toilets'           => 'Tandas',
            'nav_history'           => 'Sejarah',
            'nav_my_toilets'        => 'Tandas Saya',
            'nav_profile_picture'   => 'Gambar Profil',
            'nav_change_password'   => 'Tukar Kata Laluan',
            'nav_logout'            => 'Log Keluar',
            'nav_language'          => 'Bahasa',

            // Roles & badges
            'role_admin'            => 'Pentadbir',
            'role_student'          => 'Pelajar',
            'status_active'         => 'Aktif',
            'status_inactive'       => 'Tidak Aktif',
            'status_open'           => 'Sedang Berjalan',
            'status_closed'         => 'Selesai',
            'badge_active_checkin'  => 'Daftar Masuk Aktif',
            'badge_ready'           => 'Sedia Daftar Masuk',
            'badge_ongoing'         => 'Sedang Berjalan',
            'badge_none_assigned'   => 'Tiada ditetapkan',
            'badge_custom_photo'    => 'Foto Tersuai',
            'badge_default_initial' => 'Huruf Awalan',
            'badge_new_preview'     => 'Pratonton Baru',

            // General Actions & Buttons
            'action_add'            => 'Tambah',
            'action_edit'           => 'Sunting',
            'action_delete'         => 'Padam',
            'action_save'           => 'Simpan',
            'action_save_changes'   => 'Simpan Perubahan',
            'action_cancel'         => 'Batal',
            'action_submit'         => 'Hantar',
            'action_search'         => 'Cari',
            'action_clear'          => 'Kosongkan',
            'action_filter'         => 'Tapis',
            'action_close'          => 'Tutup',
            'action_view_all'       => 'Lihat Semua',
            'action_view_details'   => 'Lihat Butiran',
            'action_view_history'   => 'Lihat Sejarah',
            'action_view_toilet'    => 'Lihat Tandas',
            'action_back_to_login'  => 'Kembali ke Log Masuk',
            'action_sign_in'        => 'Log Masuk',
            'action_remove'         => 'Buang',

            // Login Page
            'login_title'           => 'Log Masuk',
            'login_feature_camera'  => 'Bukti Bergambar Daftar Masuk / Keluar',
            'login_feature_history' => 'Sejarah Kebersihan Lengkap',
            'login_feature_rbac'    => 'Kawalan Akses Berasaskan Peranan',
            'login_username_label'  => 'Nama Pengguna',
            'login_password_label'  => 'Kata Laluan',
            'login_username_placeholder' => 'Masukkan nama pengguna anda',
            'login_password_placeholder' => 'Masukkan kata laluan anda',
            'login_error_empty'     => 'Sila masukkan nama pengguna dan kata laluan anda.',
            'login_error_invalid'   => 'Nama pengguna atau kata laluan tidak sah. Sila cuba lagi.',
            'db_not_setup_title'    => 'Pangkalan data belum disediakan!',
            'db_not_setup_desc'     => 'Sila jalankan pemasangan terlebih dahulu sebelum log masuk.',
            'db_run_setup_now'      => 'Jalankan Pemasangan Sekarang',

            // Change Password Page
            'cp_title'              => 'Tukar Kata Laluan',
            'cp_subtitle_must'      => 'Selamat datang! Sila tetapkan kata laluan baru sebelum meneruskan.',
            'cp_subtitle_normal'    => 'Kemas kini kata laluan akaun anda.',
            'cp_alert_must'         => 'Anda dikehendaki menetapkan kata laluan baru sebelum mengakses sistem.',
            'cp_card_title'         => 'Tetapan Kata Laluan',
            'cp_current_password'   => 'Kata Laluan Semasa',
            'cp_current_placeholder'=> 'Masukkan kata laluan semasa',
            'cp_new_password'       => 'Kata Laluan Baru',
            'cp_new_placeholder'    => 'Sekurang-kurangnya 8 aksara',
            'cp_hint'               => 'Sekurang-kurangnya 8 aksara. Gunakan gabungan huruf dan nombor.',
            'cp_confirm_password'   => 'Sahkan Kata Laluan Baru',
            'cp_confirm_placeholder'=> 'Masukkan semula kata laluan baru',
            'cp_submit_btn'         => 'Simpan Kata Laluan',
            'cp_error_current'      => 'Kata laluan semasa tidak betul.',
            'cp_error_length'       => 'Kata laluan baru mestilah sekurang-kurangnya 8 aksara.',
            'cp_error_match'        => 'Kata laluan tidak sepadan.',
            'cp_success'            => 'Kata laluan berjaya ditukar!',

            // User Dashboard (My Toilets)
            'user_dash_title'       => 'Tandas Tugasan Saya',
            'user_dash_welcome'     => 'Selamat kembali, %s! Pilih tandas untuk mula daftar masuk.',
            'user_dash_assigned_count' => 'Anda mempunyai %d tandas ditetapkan. Klik untuk daftar masuk.',
            'user_dash_empty_title' => 'Tiada Tandas Ditetapkan',
            'user_dash_empty_desc'  => 'Anda belum ditetapkan ke mana-mana tandas lagi.<br>Sila hubungi pentadbir anda.',
            'user_dash_since'       => 'sejak %s',

            // Toilet Check-In / Check-Out Page
            'toilet_page_title'     => '%s — Daftar Masuk/Keluar',
            'active_session_title'  => 'Sesi Aktif — %s',
            'checked_in_at'         => 'Masa Daftar Masuk',
            'checkin_note'          => 'Nota Daftar Masuk',
            'before_photos'         => 'Foto Sebelum Cuci',
            'after_photos'          => 'Foto Selepas Cuci',
            'photos_uploaded'       => '%d dimuat naik',
            'checkout_title'        => 'Daftar Keluar — Muat Naik Foto Selepas',
            'after_photos_label'    => 'Foto Selepas Pembersihan',
            'checkout_comment_label'=> 'Ulasan / Catatan (Pilihan)',
            'checkout_comment_ph'   => 'cth. Lantai dicuci, sampah dibuang, tandas dipam…',
            'submit_checkout_btn'   => 'Hantar Daftar Keluar',
            'confirm_checkout_msg'  => 'Sahkan daftar keluar untuk %s?',
            'checkin_title'         => 'Daftar Masuk — %s',
            'checkin_alert_info'    => 'Ambil gambar tandas <strong>sebelum</strong> pembersihan. Masa daftar masuk akan direkod secara automatik.',
            'before_photos_label'   => 'Foto Sebelum Cuci (Keadaan Semasa)',
            'checkin_comment_label' => 'Ulasan / Pemerhatian (Pilihan)',
            'checkin_comment_ph'    => 'cth. Lantai basah, tong sampah penuh, tiada sabun…',
            'submit_checkin_btn'    => 'Hantar Daftar Masuk',
            'confirm_checkin_msg'   => 'Sahkan daftar masuk untuk %s?',
            'take_photo_live'       => 'Ambil Foto (Kamera Langsung)',
            'camera_hint'           => 'Tangkapan kamera langsung sahaja • Ketik di mana-mana untuk buka kamera',
            'open_camera_btn'       => 'Buka Kamera Langsung',
            'toilet_history_title'  => 'Sejarah Tandas',
            'records_count'         => '%d rekod',
            'no_history_title'      => 'Tiada Sejarah Lagi',
            'no_history_desc'       => 'Sesi yang telah selesai akan dipaparkan di sini.',
            'no_comment'            => 'Tiada ulasan',
            'none'                  => 'Tiada',
            'duration_label'        => 'Tempoh',
            'flash_not_assigned'    => 'Anda tidak ditugaskan ke tandas ini.',
            'flash_toilet_not_found'=> 'Tandas tidak dijumpai atau tidak aktif.',
            'flash_already_active'  => 'Anda sudah mempunyai sesi daftar masuk aktif untuk tandas ini. Sila daftar keluar dahulu.',
            'flash_checkin_success' => 'Daftar masuk berjaya direkodkan! Ingat untuk daftar keluar setelah selesai.',
            'flash_no_active_sess'  => 'Tiada sesi aktif dijumpai. Anda mesti daftar masuk sebelum daftar keluar.',
            'flash_checkout_success'=> 'Daftar keluar selesai! Rekod sesi berjaya disimpan.',

            // User Profile Picture
            'profile_title'         => 'Gambar Profil & Akaun',
            'profile_subtitle'      => 'Sesuaikan gambar profil anda dan urus butiran akaun.',
            'profile_custom_pic'    => 'Gambar Profil Tersuai',
            'profile_pic_desc'      => 'Muat naik foto yang jelas atau ambil swafoto menggunakan kamera.<br><small>Format disokong: JPG, PNG, WEBP, GIF (Maksimum 5MB)</small>',
            'profile_choose_img'    => 'Pilih Gambar',
            'profile_take_photo'    => 'Ambil Foto',
            'profile_save_btn'      => 'Simpan Gambar Profil',
            'profile_remove_btn'    => 'Buang Gambar Profil',
            'profile_remove_confirm'=> 'Padamkan gambar profil tersuai dan kembali ke awalan nama?',
            'profile_acc_details'   => 'Butiran Akaun',
            'profile_fullname'      => 'Nama Penuh',
            'profile_username'      => 'Nama Pengguna',
            'profile_email'         => 'Alamat Emel',
            'profile_role'          => 'Peranan',
            'profile_member_since'  => 'Ahli Sejak',
            'profile_assigned_toilets' => 'Tandas Ditetapkan (%d)',
            'profile_no_toilets'    => 'Tiada tandas ditetapkan pada masa ini.',
            'profile_camera_title'  => 'Kamera Foto Profil',
            'profile_camera_denied' => 'Akses kamera ditolak. Sila benarkan kebenaran kamera dalam penyemak imbas anda.',
            'profile_update_success'=> 'Gambar profil berjaya dikemas kini!',
            'profile_remove_success'=> 'Gambar profil telah dibuang. Kembali kepada avatar asal.',
            'profile_select_photo'  => 'Sila pilih atau ambil gambar terlebih dahulu.',
            'profile_size_error'    => 'Saiz gambar mestilah 5MB atau kurang.',
            'profile_format_error'  => 'Format fail tidak sah. Sila muat naik JPG, PNG, WEBP atau GIF.',

            // Admin Dashboard
            'admin_dash_title'      => 'Papan Pemuka Pentadbir',
            'stat_total_users'      => 'Jumlah Pengguna',
            'stat_total_toilets'    => 'Jumlah Tandas',
            'stat_active_checkins'  => 'Daftar Masuk Aktif',
            'stat_today_checkins'   => 'Daftar Masuk Hari Ini',
            'stat_today_checkouts'  => 'Daftar Keluar Hari Ini',
            'live_active_sessions'  => 'Sesi Aktif Semasa',
            'all_clear_title'       => 'Semua Selesai',
            'all_clear_desc'        => 'Tiada daftar masuk aktif pada masa ini.',
            'th_toilet'             => 'Tandas',
            'th_user'               => 'Pengguna',
            'th_since'              => 'Sejak',
            'th_elapsed'            => 'Masa Berlalu',
            'th_checkin'            => 'Daftar Masuk',
            'th_checkout'           => 'Daftar Keluar',
            'th_duration'           => 'Tempoh',
            'th_status'             => 'Status',
            'th_actions'            => 'Tindakan',
            'quick_actions'         => 'Tindakan Pantas',
            'manage_users'          => 'Urus Pengguna',
            'manage_toilets'        => 'Urus Tandas',
            'view_full_history'     => 'Lihat Sejarah Penuh',
            'add_new_user'          => 'Tambah Pengguna Baru',
            'add_new_toilet'        => 'Tambah Tandas Baru',
            'recent_sessions'       => 'Sesi Terkini',
            'no_sessions_title'     => 'Tiada Sesi Lagi',
            'no_sessions_desc'      => 'Rekod daftar masuk akan muncul di sini sebaik sahaja pengguna memulakan sesi.',

            // Admin Users Management
            'users_mgmt_title'      => 'Pengurusan Pengguna',
            'users_mgmt_subtitle'   => 'Cipta dan urus akaun pelajar serta penetapan tandas.',
            'search_users_ph'       => 'Cari pengguna…',
            'th_name'               => 'Nama',
            'th_username'           => 'Nama Pengguna',
            'th_email'              => 'Emel',
            'th_role'               => 'Peranan',
            'th_toilets'            => 'Tandas',
            'no_users_found'        => 'Tiada Pengguna Dijumpai',
            'no_users_desc'         => 'Tambah pengguna pertama anda untuk bermula.',
            'modal_add_user'        => 'Tambah Pengguna Baru',
            'modal_edit_user'       => 'Sunting Pengguna',
            'form_username_req'     => 'Nama Pengguna *',
            'form_fullname_req'     => 'Nama Penuh *',
            'form_email'            => 'Emel',
            'form_role'             => 'Peranan',
            'form_temp_pass_req'    => 'Kata Laluan Sementara *',
            'form_temp_pass_hint'   => 'Pengguna perlu menukar ini semasa log masuk kali pertama.',
            'form_assign_toilets'   => 'Tetapkan Tandas',
            'form_no_toilets_yet'   => 'Tiada tandas dicipta lagi. Tambah satu dahulu.',
            'btn_create_user'       => 'Cipta Pengguna',
            'form_username_fixed'   => 'Nama pengguna tidak boleh diubah.',
            'form_reset_pass'       => 'Tetapkan Semula Kata Laluan',
            'form_reset_pass_opt'   => '(biarkan kosong untuk kekalkan)',
            'form_reset_pass_hint'  => 'Jika ditetapkan, pengguna dipaksa tukar pada log masuk seterusnya.',
            'confirm_delete_user'   => 'Padam pengguna \'%s\'? Ini juga akan memadam semua sejarah sesi mereka.',
            'flash_user_created'    => 'Pengguna \'%s\' berjaya dicipta.',
            'flash_user_updated'    => 'Pengguna berjaya dikemas kini.',
            'flash_user_deleted'    => 'Pengguna berjaya dipadam.',
            'flash_user_self_del'   => 'Anda tidak boleh memadam akaun anda sendiri.',
            'flash_username_taken'  => 'Nama pengguna \'%s\' telah diambil.',
            'flash_user_req_fields' => 'Nama pengguna, nama penuh dan kata laluan sementara diperlukan.',

            // Admin Toilets Management
            'toilets_mgmt_title'    => 'Pengurusan Tandas',
            'toilets_mgmt_subtitle' => 'Tambah, sunting dan urus semua tandas yang dipantau.',
            'no_toilets_title'      => 'Tiada Tandas Lagi',
            'no_toilets_desc'       => 'Tambah tandas pertama anda untuk memulakan pemantauan kebersihan.',
            'th_location'           => 'Lokasi',
            'th_assigned_users'     => 'Pengguna Ditetapkan',
            'th_total_sessions'     => 'Jumlah Sesi',
            'th_active'             => 'Aktif',
            'modal_add_toilet'      => 'Tambah Tandas Baru',
            'modal_edit_toilet'     => 'Sunting Tandas',
            'form_toilet_name_req'  => 'Nama / Nombor Tandas *',
            'form_toilet_name_ph'   => 'cth. T01, Blok A Lelaki, Tingkat Bawah Perempuan',
            'form_toilet_loc_ph'    => 'cth. Blok A, Tingkat Bawah',
            'form_toilet_desc_ph'   => 'Nota tambahan mengenai tandas ini…',
            'confirm_delete_toilet' => 'Padam tandas \'%s\'? Semua sejarah sesi akan hilang.',
            'flash_toilet_added'    => 'Tandas \'%s\' berjaya ditambah.',
            'flash_toilet_updated'  => 'Tandas berjaya dikemas kini.',
            'flash_toilet_deleted'  => 'Tandas berjaya dipadam.',
            'flash_toilet_del_err'  => 'Tidak boleh padam: tandas ini mempunyai sesi daftar masuk aktif.',
            'flash_toilet_name_req' => 'Nama tandas diperlukan.',

            // Admin History
            'history_mgmt_title'    => 'Sejarah Sesi',
            'history_mgmt_subtitle' => 'Rekod lengkap daftar masuk / keluar dengan bukti bergambar.',
            'history_title'         => 'Sejarah Sesi',
            'history_subtitle'      => 'Rekod lengkap daftar masuk / keluar dengan bukti bergambar.',
            'filter_all_toilets'    => 'Semua Tandas',
            'filter_all_students'   => 'Semua Pelajar',
            'filter_all_status'     => 'Semua Status',
            'filter_by_date'        => 'Tapis mengikut tarikh',
            'filter_records_found'  => '%d rekod dijumpai',
            'records_found'         => '%d rekod dijumpai',
            'delete_all_visible'    => 'Padam Semua Rekod Kelihatan (%d)',
            'confirm_delete_all'    => 'Padam SEMUA %d rekod yang kelihatan? Tindakan ini tidak boleh dibatalkan.',
            'confirm_delete_all_filtered' => 'Padam SEMUA %d rekod yang kelihatan? Tindakan ini tidak boleh dibatalkan.',
            'confirm_delete_sess'   => 'Padam rekod sesi ini untuk %s? Tindakan ini tidak boleh dibatalkan.',
            'confirm_delete_session'=> 'Padam rekod sesi ini untuk %s? Tindakan ini tidak boleh dibatalkan.',
            'flash_session_deleted' => 'Rekod sesi telah dipadam.',
            'flash_sessions_deleted'=> '%d rekod sesi berjaya dipadam.',
            'flash_history_deleted' => 'Rekod sesi telah dipadam.',
            'flash_all_history_deleted' => '%d rekod sesi berjaya dipadam.',
            'session_in_progress'   => 'Sesi Sedang Berjalan',
            'session_in_prog_desc'  => 'Pengguna telah daftar masuk tetapi belum daftar keluar.',
            'session_in_progress_hint' => 'Pengguna telah daftar masuk tetapi belum daftar keluar.',
            'no_history_found'      => 'Tiada Sejarah Dijumpai',
            'no_photos_uploaded'    => 'Tiada foto dimuat naik',
            'photos_count'          => '%d foto',
            'check_in_title'        => 'Daftar Masuk',
            'check_out_title'       => 'Daftar Keluar',
            'before_photos_count'   => 'Foto Sebelum Cuci (%d)',
            'after_photos_count'    => 'Foto Selepas Cuci (%d)',

            // Camera modal component
            'camera_modal_title'    => 'Tangkapan Kamera Langsung',
            'camera_placeholder'    => 'Kamera akan dipaparkan di sini',
            'camera_permission_hint'=> 'Benarkan kebenaran kamera apabila diminta',
            'camera_use_photos'     => 'Gunakan %d Foto',
            'camera_captured_label' => 'Foto Ditangkap',
            'flip_camera'           => 'Tukar Kamera',
            'take_photo_shutter'    => 'Ambil Foto',

            // Setup
            'setup_title'           => 'Pemasangan ToiletTrack',
            'setup_subtitle'        => 'Penyedia Pangkalan Data',
            'setup_warn'            => 'Ini akan mencipta jadual pangkalan data dan akaun pentadbir lalai.<br>Pastikan tetapan dalam <code>config/database.php</code> adalah betul.',
            'setup_warning'         => '⚠️ Ini akan mencipta jadual pangkalan data dan akaun pentadbir lalai.<br>Pastikan tetapan dalam <code>config/database.php</code> adalah betul.',
            'setup_run_btn'         => 'Jalankan Pemasangan',
            'setup_tables_ok'       => 'Jadual pangkalan data berjaya dicipta.',
            'setup_admin_seeded'    => 'Akaun pentadbir lalai dicipta: <strong>nama pengguna: admin / kata laluan: Admin@123</strong>',
            'setup_delete_warn'     => 'Sila padamkan <code>setup.php</code> selepas pemasangan selesai.',
            'setup_success_tables'  => '✅ Jadual pangkalan data berjaya dicipta.',
            'setup_success_admin'   => '✅ Akaun pentadbir lalai dicipta: <strong>nama pengguna: admin / kata laluan: Admin@123</strong>',
            'setup_delete_notice'   => '🔒 Sila padamkan <code>setup.php</code> selepas pemasangan selesai.',
            'setup_go_login'        => 'Pergi ke Log Masuk',
            'setup_back_login'      => 'Kembali ke Log Masuk',
            'setup_goto_login'      => 'Pergi ke Log Masuk →',
        ],
    ];

    return $dict;
}

/**
 * Get translated text for a key with optional formatting arguments.
 */
function __(string $key, ...$args): string {
    $lang = getCurrentLang();
    $dict = getTranslations();

    $text = $dict[$lang][$key] ?? $dict['en'][$key] ?? $key;

    if (!empty($args)) {
        return vsprintf($text, $args);
    }
    return $text;
}

/**
 * Echo escaped translated text.
 */
function __e(string $key, ...$args): void {
    echo htmlspecialchars(__($key, ...$args), ENT_QUOTES, 'UTF-8');
}

/**
 * Escapes HTML characters safely.
 */
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
