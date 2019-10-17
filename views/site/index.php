<?php

/* @var $this yii\web\View */

//print_r(Yii::$app->db->createCommand('SELECT * FROM books')->queryAll());

//print_r(Books::model()->findByAttributes(array('name'=>'222')));

echo '<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>';
echo '<script src="http://code.jquery.com/jquery-1.8.3.js"></script>';
echo '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';


//print_r($books);

//findByAttributes(array('name'=>'222')));

$this->title = 'My Yii Application';
?>



<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use \app\models\Books;
use \app\models\DBM;

//$model = new Books;
//$books = new Books();
//echo 'User::$users';


//$books = new \app\models\Books();

//$books = \app\models\Books::find()->where(['id' => '2'])->one();

//print_r(json_encode(['0'=>'32','1'=>'31']));



//Yii::$app->urlManager->createUrl(['/db' , 'action' => 'editor'])


//echo Yii::$app->urlManager->createUrl(['../views/db']);

echo Html::a('fgsdfgs', ['dbm/test']);


//echo DB::test();

?>



<script>

    $(document).ready(function(){
        $.ajax({
            type: 'POST',
            url: '<?=Url::to(['dbm/test'])?>',
            //url: '<?//=Yii::$app->urlManager->createUrl(['dbm/test'])?>',
            data: {_csrf: '<?=Yii::$app->request->getCsrfToken()?>'},
            success: function(data){
                console.log(data);
            }
        });

    });

</script>



<!--
<div class="site-index">
    <div class="jumbotron">
        <h1>Congratulations!</h1>

        <p class="lead">You have successfully created your Yii-powered application.</p>

        <p><a class="btn btn-lg btn-success" href="http://www.yiiframework.com">Get started with Yii</a></p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/doc/">Yii Documentation &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/forum/">Yii Forum &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Heading</h2>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                    fugiat nulla pariatur.</p>

                <p><a class="btn btn-default" href="http://www.yiiframework.com/extensions/">Yii Extensions &raquo;</a></p>
            </div>
        </div>

    </div>
</div>
-->