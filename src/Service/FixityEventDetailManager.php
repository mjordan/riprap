<?php
// src/Service/FixityEventDetailManager.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * This class provides a way for Riprap plugins to pass 'event_detail' and
 * 'event_outcome_detail_note' data to 'persist' plugins.
 */

class FixityEventDetailManager
{
    public function __construct(ParameterBagInterface $params = null)
    {
        $this->event_details = array('event_detail' => array(), 'event_outcome_detail_note' => array());

        $this->params = $params;
        if ($this->params->has('app.service.detailmanager.delimiter')) {
            $this->serialize_delimiter = $this->params->get('app.service.detailmanager.delimiter');
        } else {
            $this->serialize_delimiter = ';';
        }
        var_dump("Hi from the event detail manager");
    }

    /**
     * Called from within plugins who want to add details.
     *
     * @param string $key
     *    Either 'event_detail' or 'event_outcome_detail_note'.
     * @param string $value
     *    The message to add to the field identified in $key.
     *
     * @return array
     *    Associative array with 'event_detail' and 'event_outcome_detail_note'
     *    as keys whose values are an array of strings.
     */
    public function add($key, $value = '')
    {
        if (!strlen($value)) {
            return $this->event_details;
        }
        if ($key == 'event_detail') {
            // Do no duplicate values that already exist.
            if (!in_array($value, $this->event_details['event_detail'])) {
                $this->event_details['event_detail'][] = $value;
            }
        }
        if ($key == 'event_outcome_detail_note') {
            // Do no duplicate values that already exist.
            if (!in_array($value, $this->event_details['event_outcome_detail_note'])) {
                $this->event_details['event_outcome_detail_note'][] = $value;
            }
        }

        return $this->event_details;
    }

    /**
     * Getter for details. Called from within persist plugins.
     */
    public function getDetails()
    {
        return $this->event_details;
    }

    /**
     * Called from within persist plugins to prepare the data for insertion into the database.
     *
     * @param array $details
     *    An array of strings.
     *
     * @return array
     *    Associative array with 'event_detail' and 'event_outcome_detail_note'
     *    as keys whose values are a string of the corresponding imploded details.
     */
    public function serialize($details)
    {
        $event_detail = implode($this->serialize_delimiter, $details['event_detail']);
        $event_outcome_detail_note = implode($this->serialize_delimiter, $details['event_outcome_detail_note']);
        return array('event_detail' => $event_detail, 'event_outcome_detail_note' => $event_outcome_detail_note);
    }
}
