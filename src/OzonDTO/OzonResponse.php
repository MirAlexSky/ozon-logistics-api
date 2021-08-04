<?php


namespace Miralexsky\OzonLogisticsApi\OzonDTO;


class OzonResponse
{

    /**
     * @var $ozonErrors OzonError[]
     */
    public $ozonErrors = [];

    public $exception = null;

    public $request;
    public $request_data;
    public $guzzleResponse;
    public $response_content;
    public $response_data;

    public $success = false;
    private $error_message = null;

    public function getErrorMessage()
    {
        if (!is_null($this->error_message)) {
            return $this->error_message;
        } else {

            if ($this->ozonErrors) {
                $this->error_message = '';
                foreach ($this->ozonErrors as $error) {
                    $this->error_message .= "$error->error_code: $error->error_message; ";
                }
            } else {
                if ($this->exception) {
                    $this->error_message = $this->exception->getMessage();
                }
            }
        }

        return $this->error_message;
    }

    public function pushOzonError($ozonError)
    {
        $this->ozonErrors[] = $ozonError;
    }

    public function setErrorMessage($error_message)
    {
        $this->error_message = $error_message;
    }
}