<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use yii\db\Query;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        return $behaviors;
    }

    

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Return the database table list.
     *
     * @return string
     */
    public function actionGettables()
    {
        //if (Yii::$app->request->isAjax) {
            $connection = Yii::$app->get('db2');;
            $command = $connection->createCommand("show tables");
            
            $result = $command->queryAll();

            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $result;
        //}
    }

    public function actionGetfields()
    {
        $tables_fields = [];

        //if (Yii::$app->request->isAjax) {
            $req = Yii::$app->request->post();
            //var_dump($req);
            foreach($req['table_list'] as $table){
                $connection = Yii::$app->get('db2');
                $command = $connection->createCommand("DESC ".$table);
                
                $result = $command->queryAll();
                $tables_fields[] = array_merge(array('table_name' => $table), array('fields' => $result));
            }

            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $tables_fields;
        //}
    }

    public function actionGetdata()
    {
        $tables_data = [];
        $query_fields = [];
        $query_tables = [];
        $query_constraints = [];
        $rows = new Query;

        //if (Yii::$app->request->isAjax) {
            $req = Yii::$app->request->post();
            //var_dump($req);
            foreach($req['table_list'] as $table){
                if( sizeOf($table[1]) > 0 )
                   $query_tables[] = $table[0];
                foreach($table[1] as $field){
                    $query_fields[] = $table[0].'.'.$field[0];
                    if($field[1] != -1 && $field[2] != '')
                        $query_constraints[] = $table[0].'.'.$field[0] . $field[1] .'"'. $field[2].'"';
                }                
            }

            $tables_data = $rows->select($query_fields)
                                ->from($query_tables)
                                ->where(implode(' and ',$query_constraints))
                                ->all(\Yii::$app->db2);

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $tables_data;
        //}
    }
}
