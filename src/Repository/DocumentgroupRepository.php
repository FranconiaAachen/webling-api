<?php

namespace Terminal42\WeblingApi\Repository;

use Terminal42\WeblingApi\Entity\Documentgroup;
use Terminal42\WeblingApi\EntityList;
use Terminal42\WeblingApi\Query\Query;

/**
 * @method EntityList|Documentgroup[] findAll($sort = '', $direction = '')
 * @method Documentgroup              findById($id)
 * @method EntityList|Documentgroup[] findBy(Query $query, $sort = '', $direction = '')
 */
class DocumentgroupRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'documentgroup';
    }
}
