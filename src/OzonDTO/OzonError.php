<?php


namespace Miralexsky\OzonApi\OzonDTO;


class OzonError
{

    public $error_message;
    public $error_code;

    /**
     * OzonError constructor.
     * @param $error_message
     * @param $error_code
     */
    public function __construct($error_message = null, $error_code = null)
    {
        $this->error_message = $error_message;
        $this->error_code = $error_code;
    }
}