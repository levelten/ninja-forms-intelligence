<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Intel_Admin_Metaboxes_Submission extends NF_Abstracts_SubmissionMetabox
{
    public function __construct()
    {
        parent::__construct();

        $this->_title = __( 'Intelligence Subscription', 'nf_intel' );

        if( $this->sub && ! $this->sub->get_extra_value( 'intel_euid' ) ){
            remove_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        }
    }

    public function render_metabox( $post, $metabox )
    {

    }
}