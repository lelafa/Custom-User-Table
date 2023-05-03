jQuery(document).ready(function($) {
    var order;
    load_user_data(1, "name", "asc");

    $('#user-table thead th').click(function() {
        var page = parseInt($('#pagination-links .current').text());
        var order_by = $(this).attr('data-orderby');
        if ($(this).hasClass('sorted')) {
            if($(this).hasClass('desc')){
                $(this).removeClass('desc');
                $(this).addClass('asc');
            } else {
                $(this).removeClass('asc');
                $(this).addClass('desc');
            }
        } else {
            $(this).addClass('sorted desc')
        }
        if($('#user-table thead th').hasClass('sorted')){
            order = $(this).hasClass('desc') ? 'desc' : 'asc';
        }

        $('#user-table thead th').not(this).removeClass('sorted asc desc');    
        load_user_data(page, order_by, order);
    });
    

    $(document).on('click', '.pagination-link', function(event) {
        event.preventDefault();
        var page = $(this).attr('href').match(/page=(\d+)/)[1];
        var order_by = $('#user-table th.sorted').attr('data-orderby') ? $('#user-table th.sorted').attr('data-orderby') : 'name';
        
        if($('#user-table thead th').hasClass('sorted') && $('#user-table thead th').hasClass('desc')){
            order = 'desc';
         } else {
            order = 'asc';
         }

        load_user_data(page, order_by, order);
    });

    function load_user_data(page, order_by, order) {
        $('#user-table tbody').html('<tr><td colspan="3" class="loading">Loading...</td></tr>');

        var data = {
            action: 'users_sort_get_users',
            page: page,
            order_by: order_by,
            order: order,
            nonce: users_sort_vars.nonce
        };
        $.post(users_sort_vars.ajax_url, data, function(response) {
            var parsedResponse = JSON.parse(response);
    
            $('#user-table tbody').html(parsedResponse.users_html);
    
            $('#pagination-links').html(parsedResponse.pagination_html);
            $('#pagination-links a.page-numbers').addClass('pagination-link'); 
        });
    }
});
