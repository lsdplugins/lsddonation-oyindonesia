<?php

use LSDDonation\Payments;

if (!defined('ABSPATH')) exit;

if (!class_exists('OYIndonesia_VA') && class_exists('LSDDonation\Payments\Payment_Template_Method')) {

    class OYIndonesia_VA extends Payments\Payment_Template_Method
    {
        public $id = 'oyindonesia_va';

        public $country = 'ID';

        public $js_listener = 'lsdd-oyindonesia-payment'; // Hook For Javascript After Click Complete Payment Button

        // Direct Notification
        public $notification = true;

        public function __construct()
        {
            $this->setup();

            // $this->setdown();

            // Inject Thankyou Instruction based on Payment
            add_action("lsddonation/confirmation/instruction", [$this, 'instruction'], 10, 2);
        }

        /**
         * Setter Default Data
         *
         * @return void
         */
        private function setup()
        {

            $payment_data = get_option('lsdd_payment_settings');
            if (!isset($payment_data[$this->id]) || $payment_data[$this->id] == null) { // Empty and Not Isset

                $payment_data[$this->id] = array(
                    'name' => 'OY Indonesia - VA ',
                    'description' => 'Bayar melalui VA',
                    'logo' => LSDD_OYINDONESIA_URL . 'admin/images/oyindonesia.png',
                    'group' => 'payment_gateway',
                    'group_name' => 'Payment Gateway',
                    'template_class' => 'OYIndonesia_VA',
                    'instruction' => __('Silahkan cek email anda untuk melihat instruksi pembayaran', 'lsddonation-oyindonesia'),
                    'confirmation' => self::AUTOMATIC,
                    // Options
                    'production' => 'off',
                    'apikey' => '',

                    "excluded_fields" => [],
                    "required_fields" => ['lsdd_form_name', 'lsdd_form_phone', 'lsdd_form_email']
                );
                update_option('lsdd_payment_settings', $payment_data);
            }
        }

        /**
         * Reset Payment Settings
         *
         * @param array $options
         * @return void
         */
        private function setdown()
        {
            $options = get_option('lsdd_payment_settings');
            if (isset($options[$this->id])) {
                unset($options[$this->id]);
                update_option('lsdd_payment_settings', $options);
                $this->setup();
            }
        }

        /**
         * Manage Payment Method
         *
         * @return void
         */
        public function manage($payment_id)
        {
            $payment_data = get_option('lsdd_payment_settings');
            $settings = $payment_data[$payment_id];
?>

            <div id="<?php echo $payment_id; ?>_content" class="payment-editor d-hide">
                <div class="panel-header text-center">
                    <div class="panel-title h5 mt-10 float-left"><?php _e('Edit OYIndonesia_VA', 'lsddonation-oyindonesia'); ?></div>
                    <div class="panel-close float-right"><i class="icon icon-cross"></i></div>
                </div>

                <div class="panel-body">
                    <form>
                        <div class="form-group">
                            <label class="form-switch" style="width:100px;">
                                <input type="hidden" name="production" value="off" />
                                <input type="checkbox" name="production" <?php echo ($settings['production'] == 'on') ? 'checked="checked"' : ''; ?>>
                                <i class="form-icon"></i>
                                <?php _e('Production', 'lsddonation-oyindonesia'); ?>
                            </label>
                            <small for="production"><?php _e('Disable on sandbox', 'lsddonation-oyindonesia'); ?></small>

                            <a style="border-radius: 20px;padding: 5px 25px;float:right;margin-top:-35px;" href="https://learn.lsdplugins.com/docs/ekstensi-lsddonasi/ekstensi-payment-gateway/" target="_blank" class="btn btn-primary"><?php _e('Panduan', 'lsddonation-oyindonesia'); ?></a>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="name"><?php _e('Payment Name', 'lsddonation-oyindonesia'); ?></label>
                            <input class="form-input" type="text" name="name" value="<?php echo $settings['name']; ?>" placeholder="<?php echo $this->name; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="logo"><?php _e('Payment Logo', 'lsddonation-oyindonesia'); ?></label>
                            <?php if (current_user_can('upload_files')) : ?>
                                <img style="width:150px;margin-bottom:15px;" src="<?php echo ($settings['logo'] == '') ? $this->logo : esc_url($settings['logo']); ?>" />
                                <input class="form-input" type="text" style="display:none;" name="logo" value="<?php echo ($settings['logo'] == '') ? $this->logo : esc_url($settings['logo']); ?>">
                                <input type="button" value="<?php _e('Choose Image', 'lsddonation-oyindonesia'); ?>" class="lsdd_admin_upload btn col-12">
                            <?php endif; ?>
                        </div>

                        <div class="divider text-center" style="margin-top:25px;" data-content="OYIndonesia_VA Credentials"></div>

                        <div class="form-group">
                            <label class="form-label" for="apikey"><?php _e('API Key', 'lsddonation-oyindonesia'); ?></label>
                            <input class="form-input" type="text" name="apikey" value="<?php echo $settings['apikey']; ?>" placeholder="b.z1241415212515452">
                        </div>

                        <!-- Payment Instruction -->

                        <div class="divider text-center" style="margin-top:25px;" data-content="<?php _e('Instruksi', 'lsddonation-oyindonesia'); ?>"></div>

                        <div class="form-group">
                            <label class="form-label" for="instruction"><?php _e('Instruksi Pembayaran', 'lsddonation-oyindonesia'); ?></label>
                            <textarea class="form-input" name="instruction" placeholder="<?php _e('Tolong selesaikan pembayaran melalui sesuai dengan metode yang dipilih', 'lsddonation-oyindonesia'); ?>" lsd-rows="3"><?php esc_attr_e($settings['instruction']); ?></textarea>
                        </div>

                        <!-- Payment Form Settings -->

                        <div class="divider text-center" style="margin-top:25px;" data-content="<?php _e('Form', 'lsddonation'); ?>"></div>

                        <div class="form-group">
                            <label class="form-label" for="instruction">
                                <?php _e('Required Fields', 'lsddonation'); ?>
                            </label>

                            <?php
                            $forms = apply_filters("lsddonation/form/fields/payment", array());
                            $required_fields = isset($settings['required_fields']) ? $settings['required_fields'] : array();
                            ?>

                            <select multiple="multiple" name="required_fields[]" class="selectlive js-states form-select" data-placeholder="<?php _e("Choose field to Exclude", 'lsddonation'); ?>">
                                <?php foreach ($forms as $key => $item) : ?>
                                    <option value="<?php echo $item['id']; ?>" <?php echo in_array($item['id'], $required_fields) ? 'selected' : ''; ?>><?php echo $item['label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="label-description mb-0"><?php _e('fields will set to required.', 'lsddonation'); ?></p>
                        </div>

                        <!-- End Of Options -->

                    </form>
                </div>

                <div class="panel-footer">
                    <button class="btn btn-primary btn-block lsdd-payment-save" id="<?php echo $payment_id; ?>_payment"><?php _e('Simpan', 'lsddonation-oyindonesia'); ?></button>
                </div>
            </div>
            <?php
        }

        /**
         * Display Instruction in Confirmation page
         *
         * @param integer $report_id
         * @param array $report
         * @return void
         */
        public function instruction(int $report_id,  $report)
        {
            $report = (object) $report;
            $gateway = $report->gateway;

            if ($gateway == $this->id) :

                $payment_settings = lsdd_payment_active();
                $settings = $payment_settings[$gateway];
                $logo = esc_url($settings['logo']);
                $name = esc_attr($settings['name']);
                $group = esc_attr($settings['group_name']);

            ?>

                <h6 class="lsdp-mb-10 lsdp-mt-15 font-weight-bold">
                    <?php echo $group . ' - ' . $name; ?>
                </h6>
                <div class="brand-img lsdp-mb-15">
                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_url($name); ?>" class="lsd-h-50">
                </div>

<?php
                // instruction
                echo esc_attr($settings['instruction']);
            endif;
        }

        /**
         * Formatting Notification Text Template
         * this will be replacing tag {{payment}} inside notification template
         *
         * @return void
         */
        public function notification_text(object $report, string $event, string $gateway)
        {
            $payment_settings = lsdd_payment_active();
            $settings = $payment_settings[$gateway];
            $name = esc_attr($settings['name']);
            $group = esc_attr($settings['group_name']);

            $template = $group . ' - ' . $name . PHP_EOL; // Payment Gateway - Ipaymu
            $template .= __("Instruksi", 'lsddonation-oyindonesia') . PHP_EOL . esc_attr($settings['instruction'])  . PHP_EOL;
            return trim(preg_replace("/\t/", '', $template));
        }

        /**
         * Formatting Notification HTML Template
         * this will be replacing tag {{payment}} inside notification template
         *
         * @return void
         */
        public function notification_html(object $report_id, string $event, string $gateway)
        {
            return false;
        }
    }

    Payments\Payment_Registrar::register("oyindonesia_va", new OYIndonesia_VA());
}
?>