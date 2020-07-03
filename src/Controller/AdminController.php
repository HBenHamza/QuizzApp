<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Dompdf\Dompdf;
use Dompdf\Options;

use App\Service\FileUploader;

use App\Entity\Theme;


class AdminController extends AbstractController
{
    /**
    * @Route("/admin", name="admin")
    */
    public function index(Request $request)
    {
        
        $conn = $this->getDoctrine()->getManager()->getConnection();

        $query = 'SELECT * FROM `demandes` ORDER By id DESC';
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $demandes = $stmt->fetchAll(); 

        $query2 = 'SELECT * FROM `archives`';
        $stmt1 = $conn->prepare($query2);
        $stmt1->execute();
        $archives = $stmt1->fetchAll(); 

        $query3 = ' SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = "demandes"';
        $stmt2 = $conn->prepare($query3);
        $stmt2->execute();
        $fileds = $stmt2->fetchAll(); 

        
        $nb_demandes_traites = 0;
        $nb_demandes_en_cours = 0;
        foreach($demandes as $demande){
            if($demande['status'] == 0 ||  $demande['status'] == 1){
                $nb_demandes_traites++;
            }
            else{
                $nb_demandes_en_cours++;
            }
        }

        $nb_demandes_traites += count($archives);

        $taux = ($nb_demandes_traites * 100) / (count($demandes) + count($archives));

        $status = array(
            '0' => '<a href="#" class="status_btn btn btn-warning">En attente de traitement</a>',
            '1' => '<a href="#" class="status_btn btn btn-info">En cours de traitement</a>' ,
            '2' => '<a href="#" class="status_btn btn btn-danger">Refusé</a>',
            '3' => '<a href="#" class="status_btn btn btn-success">Acceptée</a>'
        );

        return $this->render('dashebord/index.html.twig',array(
            'demandes' => $demandes,
            'archives' => $archives,
            'status' => $status,
            'taux' => round($taux),
            'en_cours' => $nb_demandes_en_cours,
            'fields' => $fileds
        ));
    }
    
    /**
    * @Route("/admin/drop_field/{name}", name="drop_field")
    */
    public function drop_field($name){
        $conn = $this->getDoctrine()->getManager()->getConnection();
        
        $query = 'ALTER TABLE `demandes`
        DROP `'.$name.'`';
        $stmt = $conn->prepare($query);

        $query1 = ' SELECT *
        FROM `extra_fields`
        WHERE title = "'.$name.'"' ;
        $stmt1 = $conn->prepare($query1);
        $stmt1->execute();
        $filed = $stmt1->fetchAll();
        
        if($filed){
            $query2 = 'DELETE FROM `extra_fields`
            WHERE title LIKE "'.$name.'"';
            $stmt3 = $conn->prepare($query2);
            $stmt3->execute();
        }

        if($stmt->execute()){
            return $this->redirect($this->generateUrl('admin'));
        }
    }

    /**
    * @Route("/admin/customize_theme", name="customize_theme")
    */
    public function customizeTheme(Request $request,FileUploader $uploader)
    {
        $em = $this->getDoctrine()->getManager();
        $theme = $em->getRepository(Theme::class)->findAll()[0];

        if($_POST){
            $file = $request->files->get('theme_logo');

            $logo = !empty($file) ? $file->getClientOriginalName() 
            : $theme->getLogo();  
            
            $title = $_POST['theme_title'] ? $_POST['theme_title'] : $theme->getTitle();

            $description = $_POST['theme_description'] ? $_POST['theme_description'] : $theme->getDescription();
        
            $theme->setLogo($logo);
            $theme->setTitle($title);
            $theme->setDescription($description);

            $em->persist($theme);
            $em->flush();

            if (!empty($file)){
                $files = glob('img/theme/*'); // get all file names
                foreach($files as $file_to_delete){ // iterate files
                if(is_file($file_to_delete))
                    unlink($file_to_delete); // delete file
                }
                $uploader->upload($this->getParameter('img_dir'),$file,$file->getClientOriginalName());
            }            
            return $this->redirect($this->generateUrl('customize_theme'));
        }

        return $this->render('dashebord/customizeTheme.html.twig',array(
            'theme' => $theme
        ));
    }

    /**                                                                                   
    * @Route("/updateAPI", name="updateAPI")
     */
    public function ajaxAction(Request $request)    
    {
        if ($request->isXMLHttpRequest()) {
            $id = $request->request->get('id');
            $value = $request->request->get('value');
            
            $status = array('1','2','3');

            $conn = $this->getDoctrine()->getManager()->getConnection();
            if(in_array($value,$status)){
                $query = 'UPDATE `demandes` SET status = '.$value.' WHERE id = '.$id;
            }else{
                switch ($value) {
                    case 'delete':
                        $query = 'DELETE FROM `demandes` WHERE id = '.$id;
                        break;
                    case 'archiv':
                        $demande = 'SELECT * FROM `demandes` WHERE id = '.$id;
                        $stmt = $conn->prepare($demande);
                        $stmt->execute();
                        $query = 'INSERT INTO `archives` values(';
                        $data = $stmt->fetchAll()[0];
                        
                        foreach(array_keys($data) as $key => $value){
                            if(!empty($data[$value])){
                                if(!ctype_digit($data[$value])){
                                    $query .= '"'.$data[$value].'",';
                                }else{
                                    $query .= $data[$value].',';
                                }
                            }else{
                                $query .= 'null,';
                            }
                        }
                        $query.=')';
                        $query = str_replace(",)",")",$query);
                        $delete = 'DELETE FROM `demandes` WHERE id = '.$id;
                        $stmt = $conn->prepare($delete);
                        $stmt->execute();
                        break;
                }
            }
            $stmt = $conn->prepare($query);
            if($stmt->execute()){
                return new JsonResponse(array('status' => '200'));
            }else{
                return new JsonResponse(array('status' => '500'));
            }
        }

        return new Response('This is not ajax!', 400);
    }   
    
    /**
     * @Route("/admin/report", name="generate_report")
     */
    public function generateReport(Request $request){
        $conn = $this->getDoctrine()->getManager()->getConnection();

        $query = 'SELECT * FROM `demandes`';
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $demandes = $stmt->fetchAll(); 

        $query2 = 'SELECT * FROM `archives`';
        $stmt1 = $conn->prepare($query2);
        $stmt1->execute();
        $archives = $stmt1->fetchAll(); 

        $nb_demandes_traites = 0;
        $nb_demandes_en_cours = 0;
        foreach($demandes as $demande){
            if($demande['status'] == 0 ||  $demande['status'] == 1){
                $nb_demandes_traites++;
            }
            else{
                $nb_demandes_en_cours++;
            }
        }

        $nb_demandes_traites += count($archives);

        $taux = ($nb_demandes_traites * 100) / (count($demandes) + count($archives));

        $status = array(
            '0' => '<a href="#" class="status_btn btn btn-warning">En attente de traitement</a>',
            '1' => '<a href="#" class="status_btn btn btn-info">En cours de traitement</a>' ,
            '2' => '<a href="#" class="status_btn btn btn-danger">Refusé</a>',
            '3' => '<a href="#" class="status_btn btn btn-success">Acceptée</a>'
        );

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('pdf/index.html.twig',array(
            'demandes' => $demandes,
            'archives' => $archives,
            'status' => $status,
            'taux' => round($taux),
            'en_cours' => $nb_demandes_en_cours
        ));
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => true
        ]);
    }

     /**
     * @Route("/admin/addField", name="addField")
     */
    public function addField(){
        if($_POST){
            $conn = $this->getDoctrine()->getManager()->getConnection();

            $options = [];
            foreach($_POST as $key => $post_element){
                if(strpos($key,'option_') !== false){
                    $options[] = $post_element;
                }
            }
            $options = json_encode($options);
            $query = 'INSERT INTO `extra_fields` values(NULL,"'.$_POST['type'].'","'.$_POST['title'].'",\''.$options.'\')';
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $name = preg_replace('/\s+/', '_', $_POST['title']);

            $query2 = 'ALTER TABLE `demandes` ADD '.$this->transliterateString($name).' varchar(250) AFTER situation';
            $stmt2 = $conn->prepare($query2);
            $stmt2->execute();

            

            return $this->redirect($this->generateUrl('addField'));
        }
        return $this->render('dashebord/addField.html.twig');   
    }

    function transliterateString($txt) {
        $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
        return str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
    }

    /**
     * @Route("/admin/fix_availability", name="fix_availability")
     */
    public function fix_availability(){
        $conn = $this->getDoctrine()->getManager()->getConnection();
        
        $query = 'DELETE FROM `availability`';
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $query1 = 'INSERT INTO `availability` values(NULL,"'.$_POST['min_date'].'","'.$_POST['max_date'].'")';
        $stmt1 = $conn->prepare($query1);
        $stmt1->execute();

        return $this->redirect($this->generateUrl('customize_theme'));

    }

     /**
     * @Route("/delete_demande/{id}", name="delete_demande")
     */
    public function deleteDemande($id){
        $conn = $this->getDoctrine()->getManager()->getConnection();
        
        $query = 'DELETE FROM demandes WHERE id = '.$id;
        $stmt = $conn->prepare($query);
        if($stmt->execute()){
            return $this->redirect($this->generateUrl('admin'));
        }
    }

    /**
     * @Route("/update_demande", name="update_demande")
     */
    public function updateDemande(Request $request,FileUploader $uploader){
        
        $conn = $this->getDoctrine()->getManager()->getConnection();
        
        $query = 'UPDATE demandes SET commentaires = "'.$request->request->get('comment').'" WHERE id = '.
        $request->request->get('id');
       
        $stmt = $conn->prepare($query);
        
        $stmt->execute();

        $attachement = $request->files->get('attachement');

        if (!empty($attachement))
        {
            $filename1 = $attachement->getClientOriginalName();


            $uploader->upload($this->getParameter('attachements_dir').'/'.$request->request->get('id'),$attachement, $filename1);
    }

    return $this->redirect($this->generateUrl('admin'));
}
    

    
}
