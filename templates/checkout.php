<?php
/**
 */

use Rtcl\Helpers\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="rtcl-notices-wrapper">
	<?php Functions::print_notices(); ?>
</div>
<div class="rtcl-checkout-content">
	<?php do_action( 'rtcl_checkout_content' ); ?>
</div>