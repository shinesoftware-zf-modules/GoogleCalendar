<?php

namespace GoogleCalendar\Mapper;

class User extends \ZfcUser\Mapper\User
{

    public function getAll()
    {
        $select = $this->getSelect();
        $entity = $this->select($select);
        $this->getEventManager()->trigger('getall', $this, array('entity' => $entity));
        return $entity;
    }
}
