<?php if( !defined('WPINC') ) die;
/**
 * Leyka template shortcodes - the new set (v3.6+)
 *
 **/

/** Donations amount collected displaying */
add_shortcode('leyka_sum', 'leyka_shortcode_amount_collected');
add_shortcode('leyka_amount_collected', 'leyka_shortcode_amount_collected');
function leyka_shortcode_amount_collected($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'all'/0/false for all campaigns, 'current' for current campaign, int for campaign with ID given:
        'campaign_id' => 'current',
        'total_funded' => 0, // True/1 to use the "amount_total" field in counting, false/0 to use the "amount" field.
        'recurring' => 0, // True/1 to count only active recurring subscriptions amount, false/0 otherwise
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    $amount_collected = 0.0;
    $donation_params = array(
        'post_type' => Leyka_Donation_Management::$post_type,
        'nopaging' => true,
        'post_status' => 'funded',
    );

    if($atts['campaign_id']) {

        $atts['campaign_id'] = $atts['campaign_id'] === 'current' ?
            (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
            ($atts['campaign_id'] === 'all' ? false : absint($atts['campaign_id']));

        if($atts['campaign_id']) {
            $donation_params['meta_query'][] = array('key' => 'leyka_campaign_id', 'value' => esc_sql($atts['campaign_id']));
        }

    }
    if($atts['recurring']) {

        $donation_params['post_parent'] = 0;
        $donation_params['meta_query'][] = array('key' => 'leyka_payment_type', 'value' => 'rebill',);

    }

    foreach(get_posts($donation_params) as $donation) {

        $donation = new Leyka_Donation($donation);

        $amount_collected += $atts['total_funded'] ? $donation->amount_total : $donation->amount;

    }

    return apply_filters(
        'leyka_shortcode_amount_collected',
        '<span class="'.($atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode').' amount-collected '.($atts['classes'] ? esc_attr($atts['classes']) : '').'">'.$amount_collected.'</span>',
        $atts,
        $amount_collected
    );

}

/** Donations collected count displaying */
add_shortcode('leyka_donations_count', 'leyka_shortcode_donations_count');
function leyka_shortcode_donations_count($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'all'/0/false to count funded donations for all campaigns,
        // 'current' for current campaign,
        // int for campaign with ID given:
        'campaign_id' => 'current',
        'recurring' => 0, // True/1 to count only active recurring subscriptions, false/0 otherwise
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    $donation_params = array(
        'post_type' => Leyka_Donation_Management::$post_type,
        'nopaging' => true,
        'post_status' => 'funded',
    );

    if($atts['campaign_id']) {

        $atts['campaign_id'] = $atts['campaign_id'] === 'current' ?
            (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
            ($atts['campaign_id'] === 'all' ? false : absint($atts['campaign_id']));

        if($atts['campaign_id']) {
            $donation_params['meta_query'][] = array('key' => 'leyka_campaign_id', 'value' => esc_sql($atts['campaign_id']));
        }

    }
    if($atts['recurring']) {

        $donation_params['post_parent'] = 0;
        $donation_params['meta_query'][] = array('key' => 'leyka_payment_type', 'value' => 'rebill',);

    }

    $query = new WP_Query($donation_params);

    return apply_filters(
        'leyka_shortcode_donations_count',
        '<span class="'.($atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode').' donations-count '.($atts['classes'] ? esc_attr($atts['classes']) : '').'">'.$query->found_posts.'</span>',
        $atts,
        $query->found_posts
    );

}

/** Donations collected count displaying */
add_shortcode('leyka_donors_count', 'leyka_shortcode_donors_count');
function leyka_shortcode_donors_count($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'all'/0/false to count funded donations for all campaigns,
        // 'current' for current campaign,
        // int for campaign with ID given:
        'campaign_id' => 'current',
        'recurring' => 0, // True/1 to count only active recurring subscriptions, false/0 otherwise
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    $donors_params = array(
        'role__in' => array(Leyka_Donor::DONOR_USER_ROLE,),
        'number' => -1,
        'fields' => 'id',
        'meta_query' => array(),
    );

    if($atts['campaign_id']) {

        $atts['campaign_id'] = $atts['campaign_id'] === 'current' ?
            (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
            ($atts['campaign_id'] === 'all' ? false : absint($atts['campaign_id']));

        if($atts['campaign_id']) {
            $donors_params['meta_query'][] = array(
                'key' => 'leyka_donor_campaigns',
                'value' => 'i:'.absint($atts['campaign_id']).';', // A little freaky, I know, but it's the best we could think of
                'compare' => 'LIKE',
            );
        }

    }
    if($atts['recurring']) {
        $donors_params['meta_query'][] = array('key' => 'leyka_donor_type', 'value' => 'regular',);
    }

    $query = new WP_User_Query($donors_params);
    $donors_count = $query->get_total();

    return apply_filters(
        'leyka_shortcode_donors_count',
        '<span class="'.($atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode').' donors-count '.($atts['classes'] ? esc_attr($atts['classes']) : '').'">'.$donors_count.'</span>',
        $atts,
        $donors_count
    );

}

add_shortcode('leyka_donations_list', 'leyka_shortcode_donations_list');
function leyka_shortcode_donations_list($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'all'/0/false to count funded donations for all campaigns,
        // 'current' for current campaign,
        // int for campaign with ID given:
        'campaign_id' => 'current',
        'recurring' => 0, // True/1 to count only active recurring subscriptions, false/0 otherwise
        'header_text' => apply_filters('leyka_shortcode_donations_list_header', __('Donations history', 'leyka'), $atts),
        'show_header' => 1,
        'show_name' => 1,
        'show_date' => 1,
        'show_time' => 1,
        'show_campaign' => 0,
        'show_amount' => 1,
        // Possible values: // 0/false/'none' | 'display-total' | 'display-total-only'
//        'show_total_amount_as' => leyka_options()->opt('widgets_total_amount_usage'), // Decided to display only original sum
//        'show_purpose' => 1,
//        'show_comment' => 1,
        'show_type_text' => 1,
        'show_type_icon' => 1,
        'length' => isset($atts['num']) ? absint($atts['num']) : leyka_get_donations_list_per_page(),
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    $donations_params = array(
        'post_type' => Leyka_Donation_Management::$post_type,
        'post_status' => 'funded',
        'posts_per_page' => absint($atts['length']),
        'meta_query' => array(),
    );

    if($atts['campaign_id']) {

        $atts['campaign_id'] = $atts['campaign_id'] === 'current' ?
            (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
            ($atts['campaign_id'] === 'all' ? false : absint($atts['campaign_id']));

        if($atts['campaign_id']) {
            $donations_params['meta_query'][] = array('key' => 'leyka_campaign_id', 'value' => absint($atts['campaign_id']),);
        }

    }
    if($atts['recurring']) {

        $donations_params['post_parent'] = 0;
        $donations_params['meta_query'][] = array('key' => 'leyka_payment_type', 'value' => 'rebill',);

    }

    $table_columns = array();
    if($atts['show_date']) {
        $table_columns['donation_date'] = $atts['show_time'] ? __('Date / time', 'leyka') : __('Date', 'leyka');
    }
    if($atts['show_name']) {
        $table_columns['donation_donor_name'] = _x('Name', "Donation donor's name, in one word", 'leyka');
    }
    if($atts['show_type_text']) {
        $table_columns['donation_type'] = __('Type', 'leyka');
    }
    if($atts['show_amount']) {
        $table_columns['donation_amount'] = __('Amount', 'leyka');
    }
    $atts['show_total_amount_as'] = $atts['show_total_amount_as'] === 'none' ? false : $atts['show_total_amount_as'];

    $table_lines = array();
    foreach(get_posts($donations_params) as $donation) {

        $donation = new Leyka_Donation($donation);

        $line = array('donation_id' => $donation->id);

        if($atts['show_date']) {
            $line['donation_date'] = $donation->date_time_label;
        }
        if($atts['show_name']) {
            $line['donation_donor_name'] = $donation->donor_name ? $donation->donor_name : __('Anonymous', 'leyka');
        }
        if($atts['show_type_text']) {
            $line['donation_type'] = $donation->type_label;
        }
        if($atts['show_amount']) {

            $line['donation_amount'] = $donation->amount_formatted.' '.$donation->currency_label;

            if($atts['show_type_icon']) {
                $line['donation_amount'] = '<img class="donation-type-icon" src="'.LEYKA_PLUGIN_BASE_URL.'/img/dashboard/icon-donation-type-'.$donation->type.'.svg" alt="">'
                    .$line['donation_amount'];
            }

        }

        $table_lines[] = $line;

    }

    ob_start();?>

    <div class="<?php echo !!$atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode';?> donations-list <?php echo $atts['classes'] ? esc_attr($atts['classes']) : '';?>">

        <table class="donations-list-table">

        <?php if($atts['show_header'] && $atts['header_text']) {?>
            <caption class="title"><?php echo esc_html($atts['header_text']);?></caption>
        <?php }

        if($table_columns) {?>

            <thead>
                <tr class="list-row header-row">
                <?php foreach($table_columns as $column_id => $column_title) {?>
                    <th class="list-cell list-column <?php echo $column_id;?>">
                        <?php echo apply_filters('leyka_shortcode_donations_list_column_'.$column_id.'_label', $column_title);?>
                    </th>
                <?php }?>
                </tr>
            </thead>

        <?php }

        if( !$table_lines ) {?>
            <td colspan="<?php echo count($table_columns);?>"><?php _e('No donations yet', 'leyka');?></td>
        <?php } else {?>

            <tbody>
            <?php foreach($table_lines as $line) {?>

                <tr class="list-row">
                <?php foreach($table_columns as $column_id => $column_title) {
                    if(isset($line[$column_id])) {?>
                    <td class="list-cell <?php echo $column_id;?>">
                        <?php echo apply_filters('leyka_shortcode_donations_list_cell_'.$column_id, $line[$column_id], $line['donation_id']);?>
                    </td>
                    <?php }
                }?>
                </tr>

            <?php }?>
            </tbody>
        <?php }?>

        </table>

    </div>

<?php return apply_filters('leyka_shortcode_donations_list', ob_get_clean(), $atts, $table_columns, $table_lines);

}

add_shortcode('leyka_donations_comments_list', 'leyka_shortcode_donations_comments_list');
function leyka_shortcode_donations_comments_list($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'all'/0/false to count funded donations for all campaigns,
        // 'current' for current campaign,
        // int for campaign with ID given:
        'campaign_id' => 'current',
        'header_text' => apply_filters('leyka_shortcode_donations_comments_list_header', __('Comments', 'leyka'), $atts),
        'show_header' => 1,
        'show_name' => 1,
        'show_date' => 1,
        'show_time' => 1,
        'background_color' => 0,
        'length' => isset($atts['num']) ? absint($atts['num']) : leyka_get_donations_list_per_page(),
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    $donations_params = array(
        'post_type' => Leyka_Donation_Management::$post_type,
        'post_status' => 'funded',
        'posts_per_page' => absint($atts['length']),
        'meta_query' => array(array('key' => 'leyka_donor_comment', 'compare' => 'EXISTS',)),
    );

    if($atts['campaign_id']) {

        $atts['campaign_id'] = $atts['campaign_id'] === 'current' ?
            (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
            ($atts['campaign_id'] === 'all' ? false : absint($atts['campaign_id']));

        if($atts['campaign_id']) {
            $donations_params['meta_query'][] = array('key' => 'leyka_campaign_id', 'value' => absint($atts['campaign_id']),);
        }

    }

    $donations = get_posts($donations_params);
    if( !$donations ) {
        return '';
    }

    ob_start();?>

    <div class="<?php echo !!$atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode';?> donations-comments-list <?php echo $atts['classes'] ? esc_attr($atts['classes']) : '';?>">

        <?php if($atts['show_header'] && $atts['header_text']) {?>
        <div class="title"><?php echo esc_html($atts['header_text']);?></div>
        <?php }

    foreach($donations as $donation) {

        $donation = new Leyka_Donation($donation);?>

        <div class="comments-list-item" style="<?php echo $atts['background_color'] ? 'background-color:'.esc_attr($atts['background_color']) : '';?>">

            <div class="comment-text"><?php echo $donation->donor_comment;?></div>
            <div class="comment-footer">

            <?php if($atts['show_name']) {?>
                <div class="comment-donor-name"><?php echo mb_ucfirst($donation->donor_name);?></div>
            <?php }

            if($atts['show_date']) {?>
                <div class="comment-date"><?php echo $atts['show_time'] ? $donation->date_time_label : $donation->date_label;?></div>
            <?php }?>

            </div>

        </div>

    <?php }?>

    </div>

    <?php return apply_filters('leyka_shortcode_donations_comments_list', ob_get_clean(), $atts, $donations);

}

add_shortcode('leyka_supporters_list', 'leyka_shortcode_supporters_list');
function leyka_shortcode_supporters_list($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'all'/0/false to count funded donations for all campaigns,
        // 'current' for current campaign,
        // int for campaign with ID given:
        'campaign_id' => 'current',
        'header_text' => apply_filters('leyka_shortcode_donors_names_list_header', __('Supporters', 'leyka'), $atts),
        'show_header' => 1,
        'expandable' => 1,
        'length' => 5, // Max names in the list
        'color_special' => false, // Special elements color
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    if($atts['campaign_id']) {
        $atts['campaign_id'] = $atts['campaign_id'] === 'current' ?
            (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
            ($atts['campaign_id'] === 'all' ? false : absint($atts['campaign_id']));
    }
    $atts['length'] = absint($atts['length']) ? absint($atts['length']) : 5;

    $supporters = leyka_get_campaign_supporters_names($atts['campaign_id'], $atts['length']);
    /**
     * @var $supporters array
     * ['names'] - donors' names list (with max length of $atts['length']),
     * ['total'] - integer, full number of donor's names (i.e. max possible list length)
     */

    ob_start();?>

    <div class="<?php echo !!$atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode';?> supporters-list <?php echo $atts['classes'] ? esc_attr($atts['classes']) : '';?>">

    <?php if($atts['show_header'] && $atts['header_text']) {?>
        <div class="title"><?php echo esc_html($atts['header_text']);?></div>
    <?php }

    array_walk($supporters['names'], function(&$value){
        $value = mb_ucfirst($value);
    });?>

        <div class="list-content">

    <?php if( !$supporters['names'] ) {
        _e('No donations yet', 'leyka');
    } else if(count($supporters['names']) >= $supporters['total']) { // Only names in the list
        echo count($supporters['names']) === 1 ?
            $supporters['names'][0] :
            implode(', ', array_slice($supporters['names'], 0, -1)).' '.__('and', 'leyka').' '.end($supporters['names']);
    } else { // Names list and the number of the rest of donors ?>

        <span class="supporters-names-wrapper"><?php echo implode(', ', $supporters['names']);?></span>

        <?php $supporters_more = sprintf(__('<span class="leyka-names-remain-number">%d</span> more', 'leyka'), $supporters['total'] - count($supporters['names']));

        if($atts['expandable']) {?>

        <span class="supporters-list-more-wrapper">
            <?php _e('and', 'leyka');?>
            <a href="#" class="leyka-js-supporters-list-more special-element" style="<?php echo $atts['color_special'] ? 'color:'.esc_attr($atts['color_special']) : '';?>" data-names-per-load="<?php echo $atts['length'];?>" data-loads-remain="5" data-names-remain="<?php echo esc_attr(implode(';', $supporters['names_remain']));?>">
                <?php echo $supporters_more;?>
            </a>
        </span>

        <?php } else {
            echo $supporters_more;
        }

    }?>

        </div>

    </div>

    <?php return apply_filters('leyka_shortcode_donations_comments_list', ob_get_clean(), $atts, $supporters);

}

add_shortcode('leyka_bar', 'leyka_shortcode_campaign_card');
add_shortcode('leyka_campaign_card_new', 'leyka_shortcode_campaign_card');
function leyka_shortcode_campaign_card($atts) {

    $atts = shortcode_atts(array(
        // Possible values: 'current'/0/false for current campaign,
        // int for campaign with ID given:
        'campaign_id' => 'current',
        'recurring' => 0,

        'show_title' => 1,
        'show_image' => isset($atts['show_thumb']) ? !!$atts['show_thumb'] : 1,
        'show_progressbar' => isset($atts['show_scale']) ? !!$atts['show_scale'] : 1,
        'show_button' => 1,
        'button_text' => leyka_get_scale_button_label(),
        'show_if_finished' => isset($atts['show_finished']) ? !!$atts['show_finished'] : 1,

        'color_background' => false, // Card background color
        'color_button' => false, // Submit background color
        'color_fulfilled' => false, // Progressbar fulfilled part color
        'color_unfulfilled' => false, // Progressbar unfulfilled part color
        'classes' => '', // HTML classes for the shortcode wrapper
        'unstyled' => 0, // True/1 to use Leyka styling for the output, false/0 otherwise
    ), $atts);

    $atts['campaign_id'] = $atts['campaign_id'] === 'current' || empty($atts['campaign_id']) ?
        (get_post() && get_post()->post_type === Leyka_Campaign_Management::$post_type ? get_the_ID() : false) :
        absint($atts['campaign_id']);

    $campaign = new Leyka_Campaign($atts['campaign_id']);
    if( !$campaign->id || !$campaign->title ) {
        return apply_filters('leyka_shortcode_campaign_card_no_campaign_found', '', $atts);
    } else if( !$atts['show_finished'] && $campaign->is_finished ) {
        return apply_filters('leyka_shortcode_campaign_card_campaign_finished', '', $atts);
    }

    ob_start();?>

    <div class="<?php echo !!$atts['unstyled'] ? 'leyka-shortcode-custom-styling' : 'leyka-shortcode';?> campaign-card <?php echo $atts['classes'] ? esc_attr($atts['classes']) : '';?>" style="<?php echo $atts['color_background'] ? 'color:'.esc_attr($atts['color_background']) : '';?>">

    <?php if($atts['show_title']) {?>
        <div class="campaign-title"><?php echo $campaign->title;?></div>
    <?php }

    if($atts['show_progressbar']) {

    }?>

    </div>

    <?php return apply_filters('leyka_shortcode_campaign_card', ob_get_clean(), $atts, $campaign);

}