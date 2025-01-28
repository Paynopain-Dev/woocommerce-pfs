<?php
/*
Possible statuses:
1 => 'ACCOUNT_NOT_FOUND',
2 => 'LOGIN_REQUIRED',
3 => 'PENDING_VALIDATION',
4 => 'CONNECTED',
5 => 'REFRESH_TOKEN'
*/

$show_onboarding = true;
$account = new Paylands_Woocommerce_Account_Connect();

if ($account->is_account_set()) {
    // Credentials are already set, so onboarding is complete
    $show_onboarding = false;
} else {
    $status = $account->get_onboarding_status();
}

?>

<div>
    <div id="wc-paylands-get-started-container">
        <div id="wc-paylands-get-started-body">
           
            <?php woocommerce_paylands_print_logo_html(); ?>
            
            <?php 
            /***********************
             * Start ONBOARDING
             ***********************/
            if ($show_onboarding) { ?>

                <?php 
                // Pending validation
                if ($status == "PENDING_VALIDATION") { ?>

                    <div id="wc-paylands-get-started">
                        <h1 class="wc-paylands-main-title"><?php _e( 'Your Paylands account is connected. We are validating your business.', 'paylands-woocommerce');?></h1>    
                        <div class="paylands-cols">
                            <div class="paylands-col1">
                                <div id="paylands-login-info">
                                    <p class="paylands-login-row"><label><?php _e( 'Business ID:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_business_id();?></b></p>
                                    <p class="paylands-login-row"><label><?php _e( 'Name:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_business_name();?></b></p>
                                    <p class="paylands-login-row"><label><?php _e( 'Email:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_business_email();?></b></p>
                                    <p class="paylands-login-row"><label><?php _e( 'Contact:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_contact_name();?></b></p>
                                    <p class="paylands-login-row"><a id="wc-paylands-disconnect-button" href=""><?php _e( 'Disconnect account', 'paylands-woocommerce');?></a></p>
                                </div>
                                <p><?php _e( 'You will soon receive an email notifying you of the status of your business so you can start selling as soon as possible.', 'paylands-woocommerce');?></p>
                                <p><?php _e( 'If we need additional information, we will let you know.', 'paylands-woocommerce');?></p>                 
                                <p><?php _e( 'Thank you!', 'paylands-woocommerce');?></p>
                            </div>
                            <div class="paylands-col1">
                                <img id="get-starged-image" src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/pasarela-pagos-online.png', PAYLANDS_PLUGIN_FILE ) ); ?>" alt=""/>
                            </div>
                        </div>
                    </div>

                <?php 
                // Login
                } else { ?>

                    <div id="wc-paylands-get-started">
                        <h1 class="wc-paylands-main-title"><?php _e( 'Start accepting payments with Woocommerce Paylands', 'paylands-woocommerce');?></h1>
                        <div class="paylands-cols">
                            <div class="paylands-col1">
                                <p><?php _e( 'Paylands is the perfect payment gateway for online eCommerce stores that need to securely and conveniently charge their customers.', 'paylands-woocommerce');?></p>
                                <p><?php _e( 'No matter if your company is large or small, Paylands offers the most innovative payment tools and the best customer support.', 'paylands-woocommerce');?></p>                 
                                <p><?php _e( 'Create your account in just a few steps.', 'paylands-woocommerce');?></p>
                                <a href="<?php echo $account->get_create_account_url();?>"  target="_blank" class="wc-paylands-button"><?php _e( 'Okay, let’s do this!', 'paylands-woocommerce');?></a>
                                <a id="wc-paylands-login-button" href="" class="wc-paylands-button wc-paylands-button-secondary"><?php _e( 'I already have an account', 'paylands-woocommerce');?></a>
                                <div id="wc-paylands-login-form-container" style="display: none;">
                                    <h3><?php _e( 'Log in with your Paylands account', 'paylands-woocommerce');?></h3>
                                    <p class="wc-paylands-small-text"><?php _e( 'If you don’t have an account, create one by registering your business', 'paylands-woocommerce');?> <a href="<?php echo $account->get_create_account_url();?>" target="_blank"><?php _e( 'here', 'paylands-woocommerce');?></a>.</p>
                                    <form id="wc-paylands-login-form" action="" method="">
                                        <div class="wc-paylands-form-row">
                                            <label for="wc-paylands-login-email"><?php _e( 'Email', 'paylands-woocommerce');?></label>
                                            <input id="wc-paylands-login-email" name="wc-paylands-login-email" type="email"/>
                                        </div>
                                        <div class="wc-paylands-form-row">
                                            <label for="wc-paylands-login-pass"><?php _e( 'Password', 'paylands-woocommerce');?></label>
                                            <input id="wc-paylands-login-pass" name="wc-paylands-login-pass" type="password"/>
                                        </div>
                                        <div class="wc-paylands-form-row">
                                            <a id="wc-paylands-login-send-button" href="" class="wc-paylands-button"><?php _e( 'Log in', 'paylands-woocommerce');?></a>
                                        </div>
                                        <div id="wc-paylands-login-form-message" style="display: none;"></div>
                                    </form>
                                </div>
                            </div>
                            <div class="paylands-col1">
                                <img id="get-starged-image" src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/pasarela-pagos-online.png', PAYLANDS_PLUGIN_FILE ) ); ?>" alt=""/>
                            </div>
                        </div>
                    </div>

                <?php } ?>
            <?php 
            /*******************
             * ONBOARDING Ok !!!
             *******************/
            } else { ?>
                <div id="wc-paylands-get-started">
                    <h1 class="wc-paylands-main-title"><?php _e( 'Your Paylands account is connected, and your business is validated. You can now start selling!', 'paylands-woocommerce');?></h1>
                    <div class="paylands-cols">
                        <div class="paylands-col1">
                            <div id="paylands-login-info">
                                <p class="paylands-login-row"><label><?php _e( 'Business ID:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_business_id();?></b></p>
                                <p class="paylands-login-row"><label><?php _e( 'Name:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_business_name();?></b></p>
                                <p class="paylands-login-row"><label><?php _e( 'Email:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_business_email();?></b></p>
                                <p class="paylands-login-row"><label><?php _e( 'Contact:', 'paylands-woocommerce');?></label> <b><?php echo $account->get_contact_name();?></b></p>
                                <p class="paylands-login-row"><a id="wc-paylands-disconnect-button" href=""><?php _e( 'Disconnect account', 'paylands-woocommerce');?></a></p>
                            </div>
                            <p><?php _e( 'Your payment methods have been successfully configured for you to start selling. Review your payment methods and activate the ones you wish to use on the website.', 'paylands-woocommerce');?></p>
                            <p><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paylands_woocommerce_gateway')?>" class="wc-paylands-button wc-paylands-button-secondary"><?php _e( 'View payment methods', 'paylands-woocommerce');?></a></p>
                        </div>
                        <div class="paylands-col1">
                            <img id="get-starged-image" src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/pasarela-pagos-online.png', PAYLANDS_PLUGIN_FILE ) ); ?>" alt=""/>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div id="wc-paylands-methods">
                <p class="wc-paylands-small-text"><?php _e('Our payment gateway integrates the most used payment methods.', 'paylands-woocommerce');?></p>
                <div id="wc-paylands-methods-logos">   
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_1@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/>       
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_2@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_3@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/>
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_4@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/cofidis.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_5@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_6@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_7@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_8@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_9@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_10@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_12@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_13@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                    <img src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/logo_14@3x.svg', PAYLANDS_PLUGIN_FILE ) ); ?>"/> 
                </div>
            </div>          

            <p class="wc-paylands-footer-small-link"><?php _e('Get to know us at', 'paylands-woocommerce');?> <a href="https://paylands.com" target="_blank">Paylands.com</a></p>
            <?php woocommerce_paylands_print_help_link(); ?>
        </div>
    </div>

    
    <div id="wc-paylands-onboarding-how">
        <div id="wc-paylands-onboarding-how-header">
            <h2><?php _e( 'How does it work?', 'paylands-woocommerce');?></h2>
        </div>
        <div id="wc-paylands-onboarding-steps">
            <div class="wc-paylands-step">
                <div class="wc-paylands-step-num">1</div>
                <h3><?php _e( 'Create and connect your account', 'paylands-woocommerce');?></h3>
                <p><?php _e( 'The first step is to create your Paylands account. A form will open where you need to fill in your company’s relevant details for validation. You will quickly receive an email with access to your account.', 'paylands-woocommerce');?></p>
            </div>
            <div class="wc-paylands-step">
                <div class="wc-paylands-step-num">2</div>
                <h3><?php _e( 'We validate your business', 'paylands-woocommerce');?></h3>
                <p><?php _e( 'Once you connect your account with the login details we’ve sent by email, we will validate your business information. From here, you can check the status of the validation process.', 'paylands-woocommerce');?></p>
            </div>
            <div class="wc-paylands-step">
                <div class="wc-paylands-step-num">3</div>
                <h3><?php _e( 'Start selling', 'paylands-woocommerce');?></h3>
                <p><?php _e( 'Once your account is validated, all you need to do is choose which payment methods you want to use in your store, and you can start selling.', 'paylands-woocommerce');?></p>
            </div>
        </div>
    </div>    

</div>

<script type="text/javascript">

    jQuery(document).ready(function() {

        jQuery('#wc-paylands-login-button').on('click', function(e) {
            e.preventDefault();
            showLoginForm();
        });

        jQuery('#wc-paylands-login-send-button').on('click', function(e) {
            e.preventDefault();
            submitLoginForm();
        });

        jQuery('#wc-paylands-login-pass').on('keyup', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                submitLoginForm();
            }
        });

        jQuery('#wc-paylands-login-email').on('change', function(e) {
            e.preventDefault();
            validateNotEmpty('wc-paylands-login-email', '<?php _e( 'Por favor, introduce un usuario', 'paylands-woocommerce'); ?>');
        });

        jQuery('#wc-paylands-login-pass').on('change', function(e) {
            e.preventDefault();
            validateNotEmpty('wc-paylands-login-pass', '<?php _e( 'Por favor, introduce una contraseña', 'paylands-woocommerce'); ?>');
        });

        jQuery('#wc-paylands-disconnect-button').on('click', function(e) {
            e.preventDefault();
            if (confirm('<?php _e( '¿Estas seguro que quieres desconectar la cuenta? Los ajustes del plugin se resetearán y los medios de pago dejarán de estar disponibles.', 'paylands-woocommerce'); ?>')) {
                disconnectAccount();
            } 
        });
        
    });

    function validateNotEmpty(field_id, message) {
        var text = jQuery('#'+field_id).val();
        if (text == '') {
            showLoginError(message);
            return false;
        }
        hideLoginError();
        return true;
    }

    function showLoginError(message) {
        jQuery('#wc-paylands-login-form-message').html(message);
        jQuery('#wc-paylands-login-form-message').show();
    }

    function hideLoginError() {
        jQuery('#wc-paylands-login-form-message').html('');
        jQuery('#wc-paylands-login-form-message').hide();
    }

    function showLoginForm() {
        hideLoginError();
        jQuery('#wc-paylands-login-form-container').toggle('slide');
        jQuery('#wc-paylands-buttons').hide();
    }

    function hideLoginForm() {
        jQuery('#wc-paylands-login-form-container').hide();
        jQuery('#wc-paylands-buttons').show();
    }

    function startLoadingLogin() {
        jQuery('#wc-paylands-login-send-button').addClass('wc-paylands-loading-button');
    }

    function stopLoadingLogin() {
        jQuery('#wc-paylands-login-send-button').removeClass('wc-paylands-loading-button');
    }

    function submitLoginForm() {

        hideLoginError();
        var go_submit = false;

        var valid = validateNotEmpty('wc-paylands-login-email', '<?php _e( 'Please, provide a username', 'paylands-woocommerce'); ?>');
        if (valid) {
            var valid = validateNotEmpty('wc-paylands-login-pass', '<?php _e( 'Please, provide a password', 'paylands-woocommerce'); ?>');
            if (valid) {
                go_submit = true;
            }
        }

        if (go_submit) {
            startLoadingLogin();
            var user = jQuery('#wc-paylands-login-email').val();
            var pass = jQuery('#wc-paylands-login-pass').val();

            jQuery.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>', // La URL de admin-ajax.php
                type: 'POST',
                data: {
                    action: 'paylands_connect_login_account', // La acción de WordPress para manejar la solicitud AJAX
                    paylands_username: user, 
                    paylands_password: pass
                },
                dataType: 'json',
                beforeSend: function () {
                    //Before sending, like showing a loading animation
                    jQuery('.loading-wrapper').show(); //TODO
                },
                success: function(data){
                    console.log('Login');
                    console.log(data);
                    jQuery('.loading-wrapper').hide();
                    if (data.success) {
                        jQuery('#wc-paylands-login-form-message').html('<?php _e( '¡Cuenta conectada!', 'paylands-woocommerce');?>');
                        hideLoginForm();
                        location.reload();
                    }else{
                        showLoginError(data.data);
                    }
                    stopLoadingLogin();
                },
                error: function(data) {
                    showLoginError('<?php _e( 'An error has occurred, please try again in a few minutes. If the error persists, please contact Paylands.', 'paylands-woocommerce');?>');
                    console.log(data);
                    stopLoadingLogin();
                }
            });
        }
    }

    function disconnectAccount() {
        console.log('disconnectAccount');
        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>', // La URL de admin-ajax.php
            type: 'POST',
            data: {
                action: 'paylands_disconnect_login_account', // La acción de WordPress para manejar la solicitud AJAX
            },
            dataType: 'json',
            beforeSend: function () {
                //Before sending, like showing a loading animation
                jQuery('.loading-wrapper').show(); //TODO
            },
            success: function(data){
                console.log('Disconnect account', data);
                location.reload();
            },
            error: function(data) {
                console.log(data);

            }
        });

    }
</script>