<?php
/**
 * Created by PhpStorm.
 * User: Аркадий
 * Date: 23.06.2015
 * Time: 14:51
 */
class Convead
{
    private static
        $initiated = false,
        $user_id,
        $userFirstName,
        $userLastName,
        $userEmail,
        $userPhone,
        $userDateOfBirth,
        $userGender,
        $log = 0
    ;

    public static function init()
    {
        if ( ! self::$initiated )
        {

            self::init_hooks();
            self::$initiated = true;
            load_plugin_textdomain('convead', false, 'convead/languages');
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        // adds "Settings" link to the plugin action page
        add_filter( 'plugin_action_links', array('Convead', 'plgn_action_links'), 10, 2 );

        //Calling a function add administrative menu.
        add_action( 'admin_menu', array('Convead', 'plgn_add_pages') );

        if(!is_admin())
        {
            add_action('wp_head', array('Convead', 'convead_main') );
        }

        add_action( 'woocommerce_before_single_product', array('Convead', 'productView'));
        add_action( 'woocommerce_cart_updated', array('Convead', 'submitCart'));
        add_action( 'woocommerce_checkout_order_processed', array('Convead', 'submitOrder'));

        register_uninstall_hook( __FILE__, array('Convead', 'delete_options') );
    }

    // Function for delete options
    public static function delete_options()
    {
        delete_option('convead_plgn_options');
    }

    //Function 'plgn_action_links' are using to create action links on admin page.
    public static function plgn_action_links($links, $file)
    {
        //Static so we don't call plugin_basename on every plugin row.
        static $this_plugin;

        if (!$this_plugin)
        {
            $this_plugin = plugin_basename(ARP_BASE);
        }

        if ($file == $this_plugin)
        {
            $settings_link = '<a href="admin.php?page=convead">' . __('Settings', 'convead') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    public static function plgn_add_pages()
    {
        add_submenu_page(
            'plugins.php',
            __( 'Convead', 'convead' ),
            __( 'Convead', 'convead' ),
            'manage_options',
            "convead",
            array('Convead', 'plgn_settings_page')
        );
        //call register settings function
        add_action( 'admin_init', array('Convead', 'plgn_settings') );
    }

    public static function plgn_options_default()
    {
        return array(
            'convead_key' => '',
            'currency_excange_rate' => '1',
            'only_product_id' => '1',
        );
    }

    // мой ключ f87f58122be091c39549e90348bd02a1
    // ключ Дениса 34c11658fac4dfefb2a4f4b1d7657020
    public static function plgn_settings()
    {
        $plgn_options_default = self::plgn_options_default();

        if (!get_option('convead_plgn_options'))
        {
            add_option('convead_plgn_options', $plgn_options_default, '', 'yes');
        }

        $plgn_options = get_option('convead_plgn_options');
        $plgn_options = array_merge($plgn_options_default, $plgn_options);

        update_option('convead_plgn_options', $plgn_options);
    }

    //Function formed content of the plugin's admin page.
    public static function plgn_settings_page()
    {
        $convead_plgn_options = self::get_params();
        $convead_plgn_options_default = self::plgn_options_default();
        $message = "";
        $error = "";

        if (isset($_REQUEST['convead_plgn_form_submit'])
            && check_admin_referer(plugin_basename(dirname(__DIR__)), 'convead_plgn_nonce_name'))
        {
            foreach($convead_plgn_options_default as $k => $v)
            {
                if($k == 'currency_excange_rate')
                {
                    $value = trim(self::request($k, $v));
                    $value = (float)str_replace(',', '.', $value);
                    $convead_plgn_options[$k] = $value;
                }
                else
                {
                    $convead_plgn_options[$k] = trim(self::request($k, $v));
                }

            }

            update_option('convead_plgn_options', $convead_plgn_options);

            $message = __("Settings saved", 'convead');
        }

        $options = array(
            'convead_plgn_options' => $convead_plgn_options,
            'message' => $message,
            'error' => $error,
        );

        echo self::loadTPL('adminform', $options);
    }

    private static function loadTPL($name, $options)
    {
        $tmpl = ( CONVEAD_PLUGIN_DIR .'tmpl/' . $name . '.php');

        if(!is_file($tmpl))
            return __('Error Load Template', 'convead');

        extract($options, EXTR_PREFIX_SAME, "convead");

        ob_start();

        include $tmpl;

        return ob_get_clean();
    }

    private static function request($name, $default=null)
    {
        return (isset($_REQUEST[$name])) ? $_REQUEST[$name] : $default;
    }

    //На всех страницах
    public static function convead_main()
    {
        $convead_plgn_options = self::get_params();
        if(!empty($convead_plgn_options['convead_key']))
        {
            $conveadSettings = '';
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;

            if($user_id > 0)
            {
                self::updateUserInfo();

                $visitor_info = array();
                if(!empty(self::$userFirstName))        $visitor_info['first_name']      = self::$userFirstName;
                if(!empty(self::$userLastName))         $visitor_info['last_name']       = self::$userLastName;
                if(!empty(self::$userEmail))            $visitor_info['email']           = self::$userEmail;
                if(!empty(self::$userPhone))            $visitor_info['phone']           = self::$userPhone;
                if(!empty(self::$userDateOfBirth))      $visitor_info['date_of_birth']   = self::$userDateOfBirth;
                if(!empty(self::$userGender))           $visitor_info['gender']          = self::$userGender;

                do_action( 'convead_visitor_info', $visitor_info );

                $conveadSettings = "visitor_uid: '{$user_id}',
                    visitor_info: {";
                $i = 0;
                foreach($visitor_info as $k => $v)
                {
                    if($i > 0)
                    {
                        $conveadSettings .= ",";
                    }
                    $conveadSettings .= "\n                        $k: '$v'";
                    $i++;
                }
                $conveadSettings .= "
                    },\n";
            }
            ?>
            <script type="text/javascript">
                window.ConveadSettings = {
                    <?php echo $conveadSettings; ?>                    app_key: '<?php echo $convead_plgn_options['convead_key']; ?>'
                };
                (function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = '//tracker.convead.io/widgets/'+ts+'/widget-<?php echo $convead_plgn_options['convead_key']; ?>.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
            </script>
            <?php
        }
    }

    /** Submit product
     * @param $id
     * @param $name
     * @throws Exception
     */
    public static function productView()
    {
        $convead_plgn_options = self::get_params();
        if(!empty($convead_plgn_options['convead_key']))
        {
            global $wp_query;
            $uri = get_permalink($wp_query->post);
            $product_cats = wp_get_post_terms( $wp_query->post->ID, 'product_cat', array( "fields" => "ids" ) );
            $category_id = (is_array($product_cats) && count($product_cats)) ? $product_cats[0] : 0;
            ?>
            <script type="text/javascript">
                convead('event', 'view_product', {
                    product_id: <?php echo $wp_query->post->ID; ?>,
                    category_id: <?php echo $category_id; ?>,
                    product_name: '<?php echo $wp_query->post->post_title; ?>',
                    product_url: '<?php echo $uri; ?>'
                });
            </script>
            <?php
        }
    }

    /** Submit order to convead
     * @param $order_id
     */
    public static function submitOrder($order_id)
    {
        $convead_plgn_options = self::get_params();
        if(!empty($convead_plgn_options['convead_key']))
        { 
            require_once ( CONVEAD_PLUGIN_DIR . 'lib/ConveadTracker.php');

            self::updateUserInfo();

            $url = $_SERVER["HTTP_HOST"];

            $order = wc_get_order( $order_id );

            $visitor_info = array();
            $first_name = self::getValue($order->billing_first_name, self::$userFirstName);
            if($first_name !== false){
                $visitor_info['first_name'] = $first_name;
            }

            $last_name = self::getValue($order->billing_last_name, self::$userLastName);
            if($last_name !== false){
                $visitor_info['last_name'] = $last_name;
            }

            $email = self::getValue($order->billing_email, self::$userEmail);
            if($email !== false){
                $visitor_info['email'] = $email;
            }

            $phone = self::getValue($order->billing_phone, self::$userPhone);
            if($phone !== false){
                $visitor_info['phone'] = $phone;
            }

            if(!empty(self::$userDateOfBirth)){
                $visitor_info['date_of_birth'] = self::$userDateOfBirth;
            }
            if(!empty(self::$userGender)){
                $visitor_info['gender'] = self::$userGender;
            }

            do_action( 'convead_visitor_info', $visitor_info );

            $guestUID = isset($_COOKIE['convead_guest_uid']) ? $_COOKIE['convead_guest_uid'] : '';

            $ConveadTracker = new ConveadTracker(
                $convead_plgn_options['convead_key'],
                $url,
                $guestUID,
                self::$user_id,
                $visitor_info
            );

            $items = array();


            $line_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );

            $total = $order->get_total();
            $shipping = $order->get_total_shipping();
            $order_total = $total - $shipping;

            if(is_array($line_items) && count($line_items))
            {
                foreach ($line_items as $item_id => $item)
                {
                    $pid = (!empty($item['variation_id']) && !$convead_plgn_options['only_product_id'])
                        ? $item['variation_id'] : $item['product_id'];

                    $price = $item['line_subtotal']
                        / (float)$item['qty']
                        * $convead_plgn_options['currency_excange_rate'];

                    $product = new stdClass();
                    $product->product_id = (int)$pid;
                    $product->qnt = (float)$item['qty'];
                    $product->price = $price;

                    $items[] = $product;
                }
            }

            $return = $ConveadTracker->eventOrder($order_id, $order_total, $items);
        }
    }

    private static function getValue($value, $default)
    {
        if(empty($value) && empty($default)){
            return false;
        }
        return (!empty($value)) ? $value : $default;
    }

    /** Submit cart to convead
     * @param $order_number
     * @param $order_total
     * @param $items
     */
    public static function submitCart()
    {
        $convead_plgn_options = self::get_params();
        if(!empty($convead_plgn_options['convead_key']))
        {
            require_once ( CONVEAD_PLUGIN_DIR . 'lib/ConveadTracker.php');

            $url = $_SERVER["HTTP_HOST"];

            self::updateUserInfo();

            $visitor_info = array();
            if(!empty(self::$userFirstName)){
                $visitor_info['first_name'] = self::$userFirstName;
            }
            if(!empty(self::$userLastName)){
                $visitor_info['last_name'] = self::$userLastName;
            }
            if(!empty(self::$userEmail)){
                $visitor_info['email'] = self::$userEmail;
            }
            if(!empty(self::$userPhone)){
                $visitor_info['phone'] = self::$userPhone;
            }
            if(!empty(self::$userDateOfBirth)){
                $visitor_info['date_of_birth'] = self::$userDateOfBirth;
            }
            if(!empty(self::$userGender)){
                $visitor_info['gender'] = self::$userGender;
            }

            do_action( 'convead_visitor_info', $visitor_info );

            $guestUID = isset($_COOKIE['convead_guest_uid']) ? $_COOKIE['convead_guest_uid'] : '';

            $ConveadTracker = new ConveadTracker( $convead_plgn_options['convead_key'], $url, $guestUID, self::$user_id, $visitor_info );

            $cart = WC()->cart->get_cart();

            $cartValue = $products = array();
            $sessionCartValue = unserialize(WC()->session->get('convead_cart_value', ''));
            $cartChanged = false;

            self::log('Event upldate cart '. date('Y-m-d h:i:s'));
            self::log('$sessionCartValue');
            self::log($sessionCartValue);

            if(count($cart))
            {
                self::log('Count cart = '.count($cart));

                foreach ($cart as $k => $v)
                {
                    $pid = (!empty($v['variation_id']) && !$convead_plgn_options['only_product_id'])
                        ? $v['variation_id'] : $v['product_id'];

                    $price = $v['data']->price * $convead_plgn_options['currency_excange_rate'];
                    $products[] = array(
                        "product_id" => $pid,
                        "qnt" => $v['quantity'],
                        "price" => $price,
                    );

                    $cartValue[$k] = $v['quantity'];
                    if(!isset($sessionCartValue[$k])
                        || $sessionCartValue[$k] != $v['quantity'])
                    {
                        self::log('Cart changed by cart product count '.$v['quantity'].' != session product count '
                            .(float)$sessionCartValue[$k].' Product ID = '.$pid);
                        $cartChanged = true;
                    }

                    if(isset($sessionCartValue[$k])){
                        unset($sessionCartValue[$k]);
                    }
                }
            }

            if(count($sessionCartValue))
            {
                self::log('Cart changed by not empty deleted session products. $sessionCartValue:');
                self::log($sessionCartValue);

                $cartChanged = true;
            }
            self::log('$cartValue');
            self::log($cartValue);

            self::log('Cart changed: ' . (int)$cartChanged. "\r\n\r\n");

            if($cartChanged)
            {
                $return = $ConveadTracker->eventUpdateCart($products);
                WC()->session->set('convead_cart_value', serialize($cartValue));
            }
        }
    }

    private static function updateUserInfo()
    {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_data = get_user_meta( $user_id );

        self::$user_id = $user_id;

        if(!empty($user_data['first_name'][0]))
            self::$userFirstName = $user_data['first_name'][0];
        if(!empty($user_data['last_name'][0]))
            self::$userLastName = $user_data['last_name'][0];
        if(!empty($current_user->data->user_email))
            self::$userEmail = $current_user->data->user_email;
        if(!empty($user_data['billing_phone'][0]))
            self::$userPhone = $user_data['billing_phone'][0];
    }

    private static function get_params()
    {
        static $params;
        if(empty($params))
        {
            $params = get_option('convead_plgn_options');
        }
        return $params;
    }

    private static function log($data)
    {
        if(self::$log)
        {
            $data = print_r($data, true);
            $file = ( CONVEAD_PLUGIN_DIR .'log/log.txt');
            file_put_contents($file, PHP_EOL . $data, FILE_APPEND);
        }
    }
}