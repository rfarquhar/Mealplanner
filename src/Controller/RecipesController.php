<?php
namespace App\Controller;

use App\Controller\AppController;

class RecipesController extends AppController
{
    // Filter to hide recipes of other users
    private $filterConditions = [];
    private $indexContains = ['Ethnicities', 'BaseTypes', 'Courses', 'PreparationTimes', 'Difficulties', 'Sources', 'Users', 'PreparationMethods'];

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    public function beforeFilter($event) {
        parent::beforeFilter($event);
        if (!$this->isPrivateCollection) {
            $this->Auth->allow([
                'findByBase', 
                'findByCourse', 
                'findByPrepMethod',
                'search', 
                'autoCompleteSearch',
                'index',
                'view',
                'display']);
        }

        //TODO: make this a setting to filter out mine (probably remember last login to get ID)
        //$this->filterConditions = array('Recipe.user_id' => $this->Auth->user('id'));
        $this->filterConditions = [];
    }

    public function isAuthorized($user) {
        // The owner of a recipe can edit and delete it
        $action = $this->request->getParam('action');
        $passParam = $this->request->getParam('pass');
        if (in_array($action, array('edit', 'delete')) && isset($passParam[0])) {
            $recipeId = (int) $passParam[0];
            if ($this->Users->isEditor($user) || $this->Recipes->isOwnedBy($recipeId, $user['id'])) {
                return true;
            }
            else {
                $this->Flash->error(__('Not Recipe Owner'));
                return false;
            }
        }

        // Just in case the base controller has something to add
        return parent::isAuthorized($user);
    }

    public function index()
    {
        $this->paginate = [
            'contain' => $this->indexContains
        ];
        $recipes = $this->paginate($this->Recipes, [
            'conditions' => $this->filterConditions
        ]);

        $this->set(compact('recipes'));
    }

    public function view($id = null)
    {
        $recipe = $this->Recipes->get($id, [
            'contain' => [
                'Ethnicities',
                'BaseTypes', 
                'Courses', 
                'PreparationTimes', 
                'Difficulties', 
                'Sources',
                'Users' => [
                    'fields' => ['name', 'id']
                ],
                'PreparationMethods',
                'Attachments', 
                'IngredientMappings' => [
                    'Ingredients' => [
                        'fields' => ['name']
                    ],
                    'Units' => [
                        'fields' => ['name', 'abbreviation']
                    ]
                ],
                'RelatedRecipes' => [
                    'Recipes' => [
                        'fields' => ['id', 'name', 'directions'],
                            'IngredientMappings' => [
                                'Ingredients' => [
                                    'fields' => ['name']
                            ],
                            'Units' => [
                                'fields' => ['name', 'abbreviation']
                            ]
                        ]
                    ] 
                ],
                'Reviews'
            ]
        ]);

        // Keep Private recipes Private
        $user = $this->Auth->user();
        if (!$this->Users->isEditor($user) && $recipe->private == 'true' && $recipe->user->id != $this->Auth->user('id')) {
            throw new UnauthorizedException(__('Recipe is private and you are not the owner.'));
        }

        $this->set('recipe', $recipe);
    }

    public function edit($id = null)
    {
        if ($id != null && !$this->Recipes->exists($id)) {
            throw new NotFoundException(__('Invalid price range'));
        }

        if ($id == null) {
            $recipe = $this->Recipes->newEntity();
        } else {
            $recipe = $this->Recipes->get($id, [
                'contain' => [
                    'Ethnicities',
                    'BaseTypes', 
                    'Courses', 
                    'PreparationTimes', 
                    'Difficulties', 
                    'Sources',
                    'Users' => [
                        'fields' => ['name', 'id']
                    ],
                    'PreparationMethods',
                    'Attachments', 
                    'IngredientMappings' => [
                        'Ingredients' => [
                            'fields' => ['name']
                        ],
                        'Units' => [
                            'fields' => ['name', 'abbreviation']
                        ]
                    ],
                    'RelatedRecipes' => [
                        'Recipes' => [
                            'fields' => ['id', 'name', 'directions'],
                                'IngredientMappings' => [
                                    'Ingredients' => [
                                        'fields' => ['name']
                                ],
                                'Units' => [
                                    'fields' => ['name', 'abbreviation']
                                ]
                            ]
                        ] 
                    ],
                    'Reviews'
                ]
            ]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $recipe = $this->Recipes->patchEntity($recipe, $this->request->getData());
            //TODO: Keep the original author just in case editor/admin edits
            $recipe->user_id = $this->Auth->user('id');
            if ($this->Recipes->save($recipe)) {
                $this->Flash->success(__('The recipe has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The recipe could not be saved. Please, try again.'));
        }
        $ethnicities = $this->Recipes->Ethnicities->find('list', ['limit' => 200, 'order' => ['Ethnicities.name']]);
        $baseTypes = $this->Recipes->BaseTypes->find('list', ['limit' => 200, 'order' => ['BaseTypes.name']]);
        $courses = $this->Recipes->Courses->find('list', ['limit' => 200, 'order' => ['Courses.name']]);
        $preparationTimes = $this->Recipes->PreparationTimes->find('list', ['limit' => 200, 'order' => ['PreparationTimes.name']]);
        $difficulties = $this->Recipes->Difficulties->find('list', ['limit' => 200]);
        $sources = $this->Recipes->Sources->find('list', ['limit' => 200, 'order' => ['Sources.name']]);
        $users = $this->Recipes->Users->find('list', ['limit' => 200, 'order' => ['Users.name']]);
        $preparationMethods = $this->Recipes->PreparationMethods->find('list', ['limit' => 200, 'order' => ['PreparationMethods.name']]);
        $units = $this->Recipes->IngredientMappings->Units->find('list', ['limit' => 200, 'order' => ['Units.name']]);
        $this->set(compact('recipe', 'ethnicities', 'baseTypes', 'courses', 'preparationTimes', 'difficulties', 'sources', 'users', 'preparationMethods', 'units'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $recipe = $this->Recipes->get($id);
        if ($this->Recipes->delete($recipe)) {
            $this->Flash->success(__('The recipe has been deleted.'));
        } else {
            $this->Flash->error(__('The recipe could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function findByBase($baseId) {

        $this->filterConditions['Recipes.base_type_id'] = $baseId;
        $this->paginate = ['contain' => $this->indexContains];
        $recipes = $this->paginate($this->Recipes, ['conditions' => $this->filterConditions]);

        $this->set(compact('recipes'));
        $this->render('index');
    }

    public function findByCourse($courseId) {
        $this->filterConditions['Recipes.course_id'] = $courseId;
        $this->paginate = ['contain' => $this->indexContains];
        $recipes = $this->paginate($this->Recipes, ['conditions' => $this->filterConditions]);

        $this->set(compact('recipes'));
        $this->render('index');
    }
    
    public function findByPrepMethod($methodId) {
        $this->filterConditions['Recipes.preparation_method_id'] = $methodId;
        $this->paginate = ['contain' => $this->indexContains];
        $recipes = $this->paginate($this->Recipes, ['conditions' => $this->filterConditions]);

        $this->set(compact('recipes'));
        $this->render('index');
    }

    public function search() {
        $term = $this->request->query('term');
        if ($term)
        {
            $this->paginate = ['contain' => $this->indexContains];
            $conditions = array_merge($this->filterConditions,['LOWER(Recipes.name) LIKE' => '%' . trim(strtolower($term)) . '%']);
            $recipes = $this->paginate($this->Recipes, ['conditions' => $conditions]);
        } else {
            $recipes = $this->paginate($this->Recipes, ['conditions' => $this->filterConditions]);
        }
        $this->set(compact('recipes'));
        $this->render('index');
    }

    public function autoCompleteSearch() {
        $searchResults = [];
        $this->RequestHandler->renderAs($this, 'json');
        $term = $this->request->query('term');
        if ($term)
        {
            $recipes = $this->Recipes->find('all', array(
                'fields' => ['Recipes.id', 'Recipes.name', 'Recipes.serving_size'],
                'conditions' => array_merge($this->filterConditions, ['LOWER(Recipes.name) LIKE ' => '%' . trim(strtolower($term)) . '%'])
            ));

            if ($recipes->count() > 0) {
                foreach ($recipes as $item) {
                    $key = $item->name;
                    $value = $item->id;
                    $servings = $item->serving_size;
                    array_push($searchResults, array('id'=>$value, 'value' => strip_tags($key), 'servings' => $servings));
                }
            } else {
                $key = "No Results for '$term' Found";
                array_push($searchResults, array('id'=>'', 'value' => $key, 'servings' => '0'));
            }

            $this->set(compact('searchResults'));
            $this->set('_serialize', 'searchResults');
        }
    }

    public function contains() {
        $this->loadModel('IngredientMappings');
        if ($this->request->is(array('post', 'put'))) {
            $ingredients = $this->request->data;
            $results = $this->Recipes->find('all', array(
                'recursive' => 0,
                'fields' => array(
                'id',
                'name',
                'COUNT(*) as matches'),
                'group' => array('Recipe.id', 'Recipe.name'),
                'joins' => array(
                    array(
                        'alias' => 'IngredientMapping',
                        'table' => 'ingredient_mappings',
                        'foreignKey' => false,
                        'conditions' => array('IngredientMapping.recipe_id = Recipe.id'),
                    ),
                ),
                'conditions' => array(
                    'IngredientMapping.ingredient_id'=> $ingredients
                ),
                'limit' => 20,
                'order' => array('matches DESC')
            ));
            $this->set('recipes', $results);       
        }
    }

}
