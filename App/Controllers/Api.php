<?php

namespace App\Controllers;

use \App\Models\User;

/**
 * Data testing for ajax calls 
 *
 * PHP version 7.0
 */
class Api extends \Core\Controller
{

    /**
     * Get the dates entered in the DB
     *
     * @return json
     */
    public function dates() {
      echo json_encode(User::getDates());
    }

    /**
     * Get the weight totals from the DB
     *
     * @return json
     */
    public function weight() {
      echo json_encode(User::getWeight());
    }

}
