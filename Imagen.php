<?php

namespace app\models;

use Yii;
use app\models\ProductoImagen;
use app\models\BannerImagen;

use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\helpers\Json;
use Imagine\Image\Box;
use Imagine\Image\Point;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "imagen".
 *
 * @property int $id
 * @property string $nombre
 * @property int $nueva
 *
 * @property ArticuloImagen[] $articuloImagens
 * @property CaracteristicaImagen[] $caracteristicaImagens
 * @property DiapositivaImagen[] $diapositivaImagens
 * @property ProductoImagen[] $productoImagens
 * @property ProductoResaltadoImagen[] $productoResaltadoImagens
 */
class Imagen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $crop_info;
    public $cual;
    public $relacion_id;

    public static function tableName()
    {
        return 'imagen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        /*return [
            [['nombre'], 'required'],
            [['nueva'], 'integer'],
            [['nombre'], 'string', 'max' => 256],
        ];*/
        return [
            [['nombre'],'file',
            'extensions' => 'jpg, png, JPG, jpeg',
            'mimeTypes' => ['image/jpeg', 'image/pjpeg', 'image/png'],
            //'maxSize' => 1024*250, 
            //'minWidth' => 517, 'maxWidth' => 517,
            //'minHeight' => 372, 'maxHeight' => 372,
            'maxFiles' => 2,
            ],
            [['crop_info', 'cual', 'relacion_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'nueva' => 'Nueva',
        ];
    }

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * Gets query for [[ArticuloImagens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArticuloImagens()
    {
        return $this->hasMany(ArticuloImagen::className(), ['imagen_id' => 'id']);
    }

    /**
     * Gets query for [[CaracteristicaImagens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCaracteristicaImagens()
    {
        return $this->hasMany(CaracteristicaImagen::className(), ['imagen_id' => 'id']);
    }

    /**
     * Gets query for [[DiapositivaImagens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDiapositivaImagens()
    {
        return $this->hasMany(DiapositivaImagen::className(), ['imagen_id' => 'id']);
    }

    /**
     * Gets query for [[ProductoImagens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductoImagens()
    {
        return $this->hasMany(ProductoImagen::className(), ['imagen_id' => 'id']);
    }

    /**
     * Gets query for [[ProductoResaltadoImagens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductoResaltadoImagens()
    {
        return $this->hasMany(ProductoResaltadoImagen::className(), ['imagen_id' => 'id']);
    }


    /**/
    public function guardarImagen()
    {

        // open image
        $nombre = Image::getImagine()->open($this->nombre->tempName);

        // rendering information about crop of ONE option 
        $cropInfo = Json::decode($this->crop_info);
        $cropInfo['dWidth'] = (int)$cropInfo['width']; //new width image
        $cropInfo['dHeight'] = (int)$cropInfo['height']; //new height image
        $cropInfo['x'] = ($cropInfo['x'] >= 0) ? $cropInfo['x'] : 0; //begin position of frame crop by X
        $cropInfo['y'] = ($cropInfo['y'] >= 0) ? $cropInfo['y'] : 0; //begin position of frame crop by Y
          


        /* $nombre_img = strtolower($this->cual) . "_" . $this->relacion_id . "_" . rand(). "_"  . rand() . '.' . $this->nombre->getExtension(); */
        $nombre_img = strtolower($this->cual) . "_" . $this->relacion_id . "_" . rand(). "_"  . rand() . '.jpg' ;
        $this->nombre = $nombre_img;
        //saving thumbnail
        if ($this->cual == 'producto')   $newSizeThumb = new Box(555, 555);
        if ($this->cual == 'fabricante')   $newSizeThumb = new Box(250, 250);
        if ($this->cual == 'categoria')   $newSizeThumb = new Box(362, 470);
        if ($this->cual == 'diapositiva-grande'){
            $newSizeThumb = new Box(1920, 1080);
            $this->cual ='diapositiva';
        }
        if ($this->cual == 'diapositiva-mediana'){
            $newSizeThumb = new Box(1275, 556);
            $this->cual ='diapositiva';
        }
        if ($this->cual == 'diapositiva-pequena'){
            $newSizeThumb = new Box(503, 300);
            $this->cual ='diapositiva';
        }
        if ($this->cual == 'articulo')   $newSizeThumb = new Box(1410, 700);

        $cropSizeThumb = new Box($cropInfo['dWidth'], $cropInfo['dHeight']);
        $cropPointThumb = new Point($cropInfo['x'], $cropInfo['y']);

        $pathThumbImage = 'upload/imagen/' . $this->cual . '/' . $nombre_img;  
        
        $nombre
            ->crop($cropPointThumb, $cropSizeThumb)
            ->resize($newSizeThumb)
            ->save($pathThumbImage, ['quality' => 90]);

        //guardar mini
        if ($this->cual == 'producto'){
            $newSizeThumb = new Box(250, 250);
            $pathThumbImage = 'upload/imagen/producto/mini/' . $nombre_img;  
            $nombre
                ->resize($newSizeThumb)
                ->save($pathThumbImage, ['quality' => 90]);
        }



        return $this->save();
    }

    /*
    * Sube imágenes sin realizar modificaciones
    */

    public function subirImagenes($imagenes, $donde, $id)
    {
        foreach ($imagenes as $file) {
            $nombre_img = $donde . "_" . $id . "_" . rand(). "_" . rand();

            $nombre = 'upload/imagen/'.strtolower($donde).'/'. $nombre_img . '.' . $file->extension;

            $file->saveAs($nombre);

            $transaction = Yii::$app->db->beginTransaction();

            try {
                $imagen = new Imagen();
                $imagen->nombre = $nombre_img . '.' . $file->extension;
                $imagen->save();

                $donde_id = strtolower($donde).'_id';
                
                /* if ($donde == 'Diapositiva'){
                    $rel_imagen = Diapositiva::findOne($id);
                    $tipo_img = ($file->extension == 'png') ? 1 : '';
                    $tipo_img = 'imagen' . $tipo_img . '_id';
                    $rel_imagen->$tipo_img = $imagen->id;
                    if (!$rel_imagen->save()) {print_r($rel_imagen->getErrors()); exit;}
                }
                elseif ($donde == 'producto-resaltado'){
                    $rel_imagen = new ProductoResaltadoImagen();
                    $donde_id = 'producto_resaltado_id';
                    $rel_imagen->cual = ($file->extension == 'png') ? 2 : 1;
                 
                    $rel_imagen->imagen_id = $imagen->id;
                    $rel_imagen->$donde_id = $id;
                    $rel_imagen->save();
                } */

                $transaction->commit();

            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
    }

    //consultar la ultima posicion
    public function UltimaPosicion($tabla, $producto_id)
    {

        $posicion = $tabla::find()->select('MAX(posicion) AS posicion')->where(['producto_id'=>$producto_id])->one();
        if (empty($posicion))
            return 1;

        return $posicion->posicion + 1;

    }

    #Mueve la posición de una imagen a una nueva posición cuando se borra una imagen
    public function moverImagenes($cual_id, $campo, $posicion)
    {
        $relacion = '\\app\models\\'.ucfirst($campo).'Imagen';
        $campo.= '_id';
        $imagenes = $relacion::find()->where([$campo=>$cual_id])->andWhere(['>','posicion',$posicion])->all();
        foreach ($imagenes as $imagen) {
            $imagen->posicion = $posicion;
            $imagen->save();
            $posicion++;
        }
    }

    public function borrarImagen($donde)
    {

       if (is_file('upload/imagen/'.$donde . $this->nombre))
            unlink('upload/imagen/'.$donde . $this->nombre);
        
        if (is_file('upload/imagen/'.$donde.'pequena/' . $this->nombre))
            unlink('upload/imagen/'.$donde.'pequena/'. $this->nombre);
        $this->delete();
    }

}
