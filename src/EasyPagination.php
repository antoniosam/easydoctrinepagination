<?php
/**
 * User: marcosamano
 * Date: 14/02/19
 */

namespace Ast\EasyDoctrinePagination;

use Doctrine\ORM\EntityManager;

class EasyPagination
{

    private $qb;
    private $qbcount;
    private $clase;
    private $wheres = 0;

    private $itemsbypage = 20;
    private $pagina = 0;

    private $results;
    private $totalresults;
    private $totalpages;

    private $primerregistro;

    function __construct(EntityManager $em, $clase)
    {
        $this->qb = $em->createQueryBuilder('b');
        $this->qb->select('a')->from($clase, 'a');
        //query count
        $this->qbcount = $em->createQueryBuilder('b');
        $this->qbcount->select('count(a.id)')->from($clase, 'a');
    }

    public function setPage($pagina){
        $page = $pagina * 1;
        if($page < 1){
            $page = 1;
        }
        $this->pagina = $page;
    }

    public function setItemsbypage($itemsbypage){
        $this->itemsbypage = $itemsbypage;
    }

    public function search($buscar,$campos,$identificadorbase = 'a'){
        $cadena = ''; foreach ($campos as $campo){ $cadena .= $identificadorbase.'.'.$campo.' LIKE :buscar OR ';} $cadena = trim($cadena,'OR ');

        $this->qb->andwhere($cadena)->setParameter('buscar',$buscar . '%');
        $this->qbcount->andwhere($cadena)->setParameter('buscar',$buscar . '%');
    }

    public function where($campo,$comparacion,$valor,$identificadorbase = 'a'){
        $this->wheres++;
        if(trim($comparacion) == 'IN'){
            $this->qb->andwhere($identificadorbase.'.'.$campo.' '.$comparacion.' (:donde'.$this->wheres.')')->setParameter('donde'.$this->wheres,$valor);
            $this->qbcount->andwhere($identificadorbase.'.'.$campo.' '.$comparacion.' (:donde'.$this->wheres.')')->setParameter('donde'.$this->wheres,$valor);
        }else{
            $this->qb->andwhere($identificadorbase.'.'.$campo.' '.$comparacion.' :donde'.$this->wheres)->setParameter('donde'.$this->wheres,$valor);
            $this->qbcount->andwhere($identificadorbase.'.'.$campo.' '.$comparacion.' :donde'.$this->wheres)->setParameter('donde'.$this->wheres,$valor);
        }
    }

    public function leftJoin($campo,$identificador,$identificadorbase = 'a'){
        $this->qb->leftJoin($identificadorbase.'.'.$campo, $identificador);
        $this->qbcount->leftJoin($identificadorbase.'.'.$campo, $identificador);
    }

    public function order($columna,$orden,$identificadorbase = 'a'){
        $this->qb->orderby($identificadorbase.'.'.$columna,($orden=='ASC'?'ASC':'DESC'));
    }

    public function execute(){
        $this->totalresults = (int) $this->qbcount->getQuery()->getSingleScalarResult();

        $this->primerregistro = (($this->pagina - 1) * $this->itemsbypage);
        $this->qb->setFirstResult($this->primerregistro);
        $this->qb->setMaxResults($this->itemsbypage);

        $this->results = $this->qb->getQuery()->getResult();
        $this->totalpages = ceil($this->totalresults / $this->itemsbypage);
    }

    private function getPages(){
        if($this->totalresults > 0){
            if ($this->totalpages <= 7) {
                $inicio = 1;
                $fin = $this->totalpages;
            } else {
                if ($this->pagina - 3 < 0) {
                    $inicio = 1;
                    $fin = $this->pagina + 3 + (3 - $this->pagina);//3 a la derecha mas los links que no se puedieron mostrar en la izquierda
                } elseif (($this->pagina + 3) > $this->totalpages) {
                    $inicio = $this->pagina - 3 - (($this->pagina + 3) - $this->totalpages);//3 a la izquierda mas los que no se puedieron mostrar el lado derecho
                    $fin = $this->totalpages;
                } else {
                    $inicio = $this->pagina - 3;
                    $fin = $this->pagina + 3;
                }
            }
            return range($inicio,$fin);
        }else{
            return [];
        }

    }

    public function getResult(){
        return [
            'totalrecords' => $this->totalresults,
            'data' => $this->results,
            'page' => $this->pagina,
            'pages' => $this->getPages(),
            'totalpages' => $this->totalpages,
            'itemsbypage' => $this->itemsbypage,
            'firstrecord' => ($this->totalresults > 0) ? (1 +$this->primerregistro):0,
            'lastrecord' => ($this->primerregistro) + count($this->results),
        ];
    }

}