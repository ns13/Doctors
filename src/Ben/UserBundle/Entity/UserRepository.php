<?php

namespace Ben\UserBundle\Entity;
 
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
 
class UserRepository extends EntityRepository
{
    public function getActive()
    {
        // Comme vous le voyez, le délais est redondant ici, l'idéale serait de le rendre configurable via votre bundle
        $delay = new \DateTime();
        $delay->setTimestamp(strtotime('2 minutes ago'));
 
        $qb = $this->createQueryBuilder('u')
            ->where('u.lastActivity > :delay')
            ->setParameter('delay', $delay)
        ;
 
        return $qb->getQuery()->getResult();
    }
    
    public function getUsers()
    {
        $qb = $this->createQueryBuilder('u')
                ->leftJoin('u.image', 'img')
                ->addSelect('img')
        ;
 
        return $qb->getQuery()->getResult();
    }

    public function search($searchParam) {
        extract($searchParam);      
       $qb = $this->createQueryBuilder('u')
                ->leftJoin('u.image', 'img')
                ->addSelect('img');
        if(!empty($keyword))
            $qb->andWhere('concat(u.familyname, u.firstname) like :keyword or u.email like :keyword u.username like :keyword or u.roles like :keyword')
                ->setParameter('keyword', '%'.$keyword.'%');

        if(!empty($ids))
            $qb->andWhere('u.id in (:ids)')->setParameter('ids', $ids);
        if(!empty($sortBy)){
            $sortBy = in_array($sortBy, array('firstname', 'familyname', 'username')) ? $sortBy : 'id';
            $sortDir = ($sortDir == 'DESC') ? 'DESC' : 'ASC';
            $qb->orderBy('p.' . $sortBy, $sortDir);
        }
        if(!empty($perPage)) $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);

       return new Paginator($qb->getQuery());
    }

    public function counter() {
        $sql = 'SELECT count(u) FROM ben\UserBundle\Entity\User u';
        $query = $this->_em->createQuery($sql);
         
      return $query->getOneOrNullResult();
    }

    // public function statsByMeds(daterange)
    // {
    //     $date = explode("-", $daterange);
    //     return  $this->fetch("select m.name as label, coalesce(sum(cm.count), 0 ) as data from meds m
    //                 left join consultation_meds cm on cm.meds_id = m.id
    //                 group by m.id 
    //                 union
    //                 select 'total' as label,  sum(count) as data from consultation_meds");
    // }
    // public function statsByConsultation($daterange)
    // {
    //     $date = explode("-", $daterange);
    //     return  $this->fetch("select c.name as label, count(*) as data from consultation c 
    //             where c.created between '".$date[0]."' and '".$date[1]."'
    //             group by c.name");
    // }

    private function fetch($query)
    {
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return  $stmt->fetchAll();
    }
}