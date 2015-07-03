<?php
namespace Search\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class SearchBehavior extends Behavior
{

    /**
     * $_defaultConfig For the Behavior.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'implementedFinders' => [
            'search' => 'findSearch'
        ],
        'implementendMethods' => [
            'filterParams' => 'filterParams'
        ],
        'field' => 'name',
        'dest_field' => 'search_data',
        'replacement' => ' ',
    ];

 
    /**
     * Appelée avant la sauvegarde par beforeSave
     * @param  Entity $entity [description]
     * @return [type]         [description]
     */
    public function searchable(Entity $entity) {
        $config = $this->config();
        $value = '';
        if ( is_array($config['field']) ){
            foreach($config['field'] as $field_name) {
                $value .= $entity->get($field_name).$config['replacement'];
            }
            $value = rtrim($value,$config['replacement']);
        } else {
            $value = $entity->get($config['field']);
        }

        //echo $config['dest_field']." => ".strtolower(Inflector::slug($value, $config['replacement']));
        $entity->set($config['dest_field'], strtolower(Inflector::slug($value, $config['replacement'])));
    }

    /**
     * Avant de sauvegarder on crée la chaine de caractères allant dans le champs search_data de la table
     * @param  Event  $event  [description]
     * @param  Entity $entity [description]
     * @return [type]         [description]
     */
    public function beforeSave(Event $event, Entity $entity){
        $this->searchable($entity);
    }


    /**
     * Callback fired from the controller.
     *
     * @param Query $query Query.
     * @param array $options The GET arguments.
     * @return \Cake\ORM\Query The Query object used in pagination.
     */
    public function findSearch(Query $query, array $options)
    {
        $liste_mots=[];
        if (isset($options['search'])) {
            $options = $options['search'];
            if (isset($options['search'])) {
                $mots = explode(" ", $options['search']);
                $NombreMots = count($mots);
                
                for ($i=0 ; $i<count($mots) ; $i++ ){
                    $mot = strtolower(Inflector::slug( trim($mots[$i]) ));
                    if (strlen($mot)>0) {
                        $liste_mots[] = $mot;
                    }
                   
                }
            }
        }
        
        foreach ($liste_mots as $mot) {
            foreach ($this->_table->searchConfiguration()->all() as $config) {
                $config->args(['search' =>$mot]);
                $config->query($query);
                $config->process();
            }
        }
        return $query;
    }


    /**
     * Returns the valid search parameter values according to those that are defined
     * in the searchConfiguration() method of the table.
     *
     * @param array $params a key value list of search parameters to use for a search.
     * @return array
     */
    public function filterParams($params)
    {
        $valid = $this->_table->searchConfiguration()->all();
        return ['search' => array_intersect_key($params, $valid)];
    }
}
