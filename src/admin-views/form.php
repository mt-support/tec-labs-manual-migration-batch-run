<?php

use Tribe\Extensions\Manual_Batch_Upgrade_6\Process_Migration;
use Tribe\Extensions\Manual_Batch_Upgrade_6\Plugin;

?>
<form method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ) ?>">
    <input type="hidden" name="action" value="<?= Process_Migration::RUN_ACTION ?>"/>
    <div class="tec-labs">

    </div>
    <select>
        <option value="10">10</option>
        <option selected value="50">50</option>
        <option value="100">100</option>
        <option value="250">250</option>
    </select>
	<?php

	wp_nonce_field( Process_Migration::RUN_ACTION, Process_Migration::RUN_NONCE );
	submit_button( __( 'Run', Plugin::TEXT_DOMAIN ) );
	?>
</form>


