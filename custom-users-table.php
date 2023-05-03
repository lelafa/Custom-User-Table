<?php
/*
Plugin Name: Custom User sort
Description: Creates a custom HTML table in the admin panel to list users filtered by roles and sorted by name and username.
*/


add_action('admin_enqueue_scripts', 'users_sort_scripts');
function users_sort_scripts($hook) {
    if ($hook == 'toplevel_page_users-sort') {
        wp_enqueue_style('users-sort-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('users-sort-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'));
        wp_localize_script('users-sort-script', 'users_sort_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('users-sort-nonce')
        ));
    }
}

add_action('admin_menu', 'users_sort_menu');
function users_sort_menu() {
    add_menu_page('Users Sort', 'Users Sort', 'manage_options', 'users-sort', 'users_sort_page', 'dashicons-admin-users', 30);
}


function users_sort_page() {
    ?>
        <div class="wrap">
            <table id="user-table" class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th data-orderby="name">Name</th>
                        <th data-orderby="user_login">Username</th>
                        <th data-orderby="role">Role</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">
                            <div class="tablenav">
                                <div class="tablenav-pages" id="pagination-links"></div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>  
<?php
}

add_action('wp_ajax_users_sort_get_users', 'users_sort_get_users');
add_action('wp_ajax_nopriv_users_sort_get_users', 'users_sort_get_users');

function users_sort_get_users() {
    check_ajax_referer('users-sort-nonce', 'nonce');
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
    $order_by = isset($_POST['order_by']) ? sanitize_text_field($_POST['order_by']) : '';
    $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : '';

    $users_per_page = 10;
    $offset = ($page - 1) * $users_per_page;

    if($order_by == "name"){
        $args = array(
            'page' => $page,
            'role' => $role,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'first_name',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => 'last_name',
                    'compare' => 'EXISTS',
                ),
            ),
            'orderby' => 'meta_value',
            'meta_key' => 'first_name',
            'order' => $order,
            'number' => $users_per_page,
            'offset' => $offset,
            'user_name' => $username,
            'count_total' => true
        );
    } else if($order_by == "role") {
        $args = array(
            'page' => $page,
            'role' => $role,
            'orderby' => 'meta_value',
            'meta_key' => 'wp_capabilities',
            'order' => $order,
            'number' => $users_per_page,
            'offset' => $offset,
            'count_total' => true
        );
    } else {
        $args = array(
            'page' => $page,
            'role' => $role,
            'orderby' => $order_by,
            'order' => $order,
            'number' => $users_per_page,
            'offset' => $offset,
            'count_total' => true
        );
    }

    $users_query = new WP_User_Query($args);
    $total_users = $users_query->get_total();
    $total_pages = ceil($total_users / $users_per_page);

    $users = $users_query->get_results();
    ob_start(); 
    foreach ($users as $user) {
        $name = $user->first_name . ' ' . $user->last_name;
        $username = $user->user_login;
        $role = implode(', ', $user->roles);
        echo '<tr><td>' . $name . '</td><td>' . $username . '</td><td>' . $role . '</td></tr>';
    }
    $users_html = ob_get_clean();

    $pagination_html = '<div id="pagination-links" class="tablenav">';
    $pagination_html .= paginate_links(array(
        'base' => add_query_arg('page', '%#%'),
        'format' => '',
        'prev_text' => 'prev',
        'next_text' => 'next',
        'total' => $total_pages,
        'current' => $page
    ));

    $pagination_html .= '</div>';

    $response = array(
        'users_html' => $users_html,
        'pagination_html' => $pagination_html
    );

    echo wp_json_encode($response);
    wp_die(); 
}  
