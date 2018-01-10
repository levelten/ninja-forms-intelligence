<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_OptIn
 */
class NF_Intel_Fields_OptIn extends NF_Abstracts_FieldOptIn
{
    protected $_name = 'intel-optin';

    protected $_section = 'common';

    protected $_type = 'intel-optin';

    protected $_templates = 'checkbox';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Intelligence OptIn', 'nf_intel' );
    }
}