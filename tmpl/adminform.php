<?php
/*
Plugin Name: Convead
Description: Social share buttons with counters likes.
Version: 1.0
Author: Arkadiy
Author URI: http://joomline.ru
*/
$enable  =  $convead_plgn_options['only_product_id']? ' checked="checked"' : '';
$disable = !$convead_plgn_options['only_product_id']? ' checked="checked"' : '';
?>
<div class="wrap">
    <div class="icon32" id="icon-options-general"></div>
    <h2><?php echo __("Convead Settings", 'convead'); ?></h2>

    <div id="message"
         class="updated fade" <?php if (!isset($_REQUEST['convead_plgn_form_submit']) || $message == "") echo "style=\"display:none\""; ?>>
        <p><?php echo $message; ?></p>
    </div>

    <div class="error" <?php if ("" == $error) echo "style=\"display:none\""; ?>>
        <p>
            <strong><?php echo $error; ?></strong>
        </p>
    </div>



    <div>
        <form name="form1" method="post" action="admin.php?page=convead" enctype="multipart/form-data">

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __("API Key", 'convead'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='convead_key'
                            type='text'
                            value='<?php echo $convead_plgn_options['convead_key']; ?>'
                            />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __("Currency to rouble exchange rate", 'convead'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='currency_excange_rate'
                            type='text'
                            value='<?php echo $convead_plgn_options['currency_excange_rate']; ?>'
                            />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __("Submit only product_id (not variation_id)", 'convead'); ?></th>
                    <td>
                        <label for="only_product_id_1"><?php echo __("Yes", 'convead'); ?></label>
                        <input
                            id="only_product_id_1"
                            name='only_product_id'
                            type='radio'
                            value='1'<?php echo $enable; ?>
                            />
                        <label for="only_product_id_1"><?php echo __("No", 'convead'); ?></label>
                        <input
                            id="only_product_id_0"
                            name='only_product_id'
                            type='radio'
                            value='0'<?php echo $disable; ?>
                            />
                    </td>
                </tr>
            </table>

            <input type="hidden" name="convead_plgn_form_submit" value="submit"/>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>

            <?php wp_nonce_field(plugin_basename(dirname(__DIR__)), 'convead_plgn_nonce_name'); ?>
        </form>
    </div>
    <br/>
    <div class="link">
        <a class="button-secondary" href="https://app.convead.io" target="_blank"><?php echo __("Go to Convead account", 'convead'); ?></a>
    </div>
</div>
