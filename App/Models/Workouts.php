<?php

namespace App\Models;

use PDO;
use \App\Token;
use \Core\View;

/**
 * User model
 *
 * PHP version 7.0
 */
class Workouts extends \Core\Model
{
    public function NewWorkOutAction($liftname) {

        $sql = 'CREATE TABLE liftname = :liftname (Reps varchar(255), Sets varchar(255), Pounds varchar(255))';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':liftname', $liftname, PDO::PARAM_STR);

        return $stmt->execute();

    }
}