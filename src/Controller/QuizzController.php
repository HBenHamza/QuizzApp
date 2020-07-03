<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Service\FileUploader;
use App\Entity\Theme;


class QuizzController extends AbstractController
{
    /**
     * @Route("/", name="quizz")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $theme = $em->getRepository(Theme::class)->findAll()[0];

        $conn = $this->getDoctrine()->getManager()->getConnection();

        $query = 'SELECT * FROM `extra_fields`';
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $fields = $stmt->fetchAll(); 

        $html_fields = [];
        //print_r($fields);die;
        foreach($fields as $field){
           if($field['type'] == 'Input text'){
                $html_fields[] = $this->getHtmlField('text',$field['title']);
            }
            if($field['type'] == 'Radio'){
                $html_fields[] = $this->getHtmlField('radio',$field['title'],json_decode($field['options']));
            }
            if($field['type'] == 'Select'){
                $html_fields[] = $this->getHtmlField('select',$field['title'],json_decode($field['options']));
            }
            if($field['type'] == 'Textarea'){
                $html_fields[] = $this->getHtmlField('textarea',$field['title']);
            }
        }

        //print_r($html_fields);die;
        if(count($html_fields) % 4 == 0) {
            $steps = count($html_fields) / 4; 
        }else{
            $steps = (int)(count($html_fields) / 4) + 1;
        }

       
        
        $get_avi = 'SELECT * FROM `availability`';
        $stmt2 = $conn->prepare($get_avi);
        $stmt2->execute();
        $avalability = $stmt2->fetchAll();

        return $this->render('quizz/index.html.twig',array(
            'theme' => $theme,
            'html_fields' => $html_fields,
            'steps' => $steps,
            'min_date' => $avalability[0]['min_date'],
            'max_date' => $avalability[0]['max_date']
        ));
    }

    function getHtmlField($type,$title,$options = null){
        switch ($type) {
            case 'text':
                $name = preg_replace('/\s+/', '_', $title);
                $name = $this->transliterateString($name);
                return '
                <div class="input-block"><input type="text" class="form-control" name="'.$name.'" placeholder="'.$title.'"></div>';
                break;

                case 'textarea':
                $name = preg_replace('/\s+/', '_', $title);
                $name = $this->transliterateString($name);
                return '
                <div class="input-block"><textarea class="form-control" name="'.$name.'" placeholder="'.$title.'"></div>';
                break;

                case 'radio':
                $name = preg_replace('/\s+/', '_', $title);
                $name = $this->transliterateString($name);
                $html = '<p>'.$title.'</p> ';
                foreach($options as $option){
                    $html .= '<div class="radio"><label><input type="radio" name="'.$name.'" value="'.$option.'">'.$option.'</label></div>';
                }
                return $html;
                break;

                case 'checkbox':
                $name = preg_replace('/\s+/', '_', $title);
                $name = $this->transliterateString($name);
                $html = '<p>'.$title.'</p> ';
                foreach($options as $option){
                    $html .= '<div class="checkbox"><label><input type="checkbox" name="'.$name.'" value="'.$option.'">'.$option.'</label></div>';
                }
                return $html;
                break;

                case 'select':
                $name = preg_replace('/\s+/', '_', $title);
                $name = $this->transliterateString($name);
                $html = '<p>'.$title.'</p> ';
                $html .= '<select class="form-control" name="'.$name.'">';
                foreach($options as $option){
                    $html .= '<option value="'.$option.'">'.$option.'</option>';
                }
                $html .= '</select>';
                return $html;
                break;
            
            default:
                return '';
                break;
        }
    }

    function transliterateString($txt) {
        $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
        return str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
    }

     /**
     * @Route("/test")
     */
    public function test(Request $request,FileUploader $uploader)
    {
        //print_r($_POST);die;
        $conn = $this->getDoctrine()->getManager()->getConnection();

        $query = 'INSERT INTO `demandes` values(Null,"'.date("d/m/Y") .'",';
        $not_wanted = array('website','terms','process','chronics','children','family_care');
        foreach (array_keys($_POST) as $index => $key) {
                if($_POST[$key]){
                    if(!in_array($key,$not_wanted)){
                        if ($key == "country" && strpos($_POST[$key], '(') !== false) {
                            $value = str_replace("(","",$_POST[$key]);
                            $value = str_replace(")","",$value);
                            $value = preg_replace('/[^A-Za-z0-9\-]/', '', $value);
                            $query .= '"'.$value.'",';
                        }else{
                            if(!ctype_digit($_POST[$key])){
                                $query .= '"'.$_POST[$key].'",';
                            }else{
                                $query .= $_POST[$key].',';
                            }
                        }
                    }
                }else{
                    if($key !== "website" && $key !== "terms" && $key !== "process"){
                        $query .= 'null,';
                    }
                }
        }

        $personal_photo = $request->files->get('personal_photo');
        $personal_identity = $request->files->get('personal_identity');

        if (!empty($personal_photo) && empty(!$personal_identity))
        {
            $filename1 = $personal_photo->getClientOriginalName();
            $filename2 = $personal_identity->getClientOriginalName();

            $query .= '"'.$filename1.'","'.$filename2.'"';

            $uploader->upload($this->getParameter('upload_dir'),$personal_photo, $filename1);
            $uploader->upload($this->getParameter('upload_dir'),$personal_identity, $filename2);  
        }else{
            $query .= 'null,null';
        }
        $query.=',NULL,0)';
        //print_r($query);die;
        $stmt = $conn->prepare($query);
        if($stmt->execute()){

            return $this->redirect($this->generateUrl('quizz'));
        }else{
            Response('Operation failed !', Response::HTTP_ERROR);        
        }
        
    }            

}
