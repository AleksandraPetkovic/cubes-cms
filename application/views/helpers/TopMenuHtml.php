<?php

class Zend_View_Helper_TopMenuHtml extends Zend_View_Helper_Abstract {

    public function topMenuHtml() {

        $cmsSitemapPageDbTable = new Application_Model_DbTable_CmsSitemapPages();
        
        $topMenuSitemapPages = $cmsSitemapPageDbTable->search(array(
            'filters' => array(
                'parent_id' => 0,
                'status' => Application_Model_DbTable_CmsSitemapPages::STATUS_ENABLED
            ),
            'order' => array(
                'order_number' => 'ASC'
            )
        ));
        //resetovanje placeholder-a
        $this->view->placeholder('topMenuHtml')->exchangeArray(array());
        $this->view->placeholder('topMenuHtml')->captureStart();
        ?>
        <ul class="nav navbar-right" id="main-menu">
            <li>
                <a href="<?php echo $this->view->baseUrl('/'); ?>">Home</a>
            </li>
            
            <?php foreach ($topMenuSitemapPages as $sitemapPage) { ?>
            
            <li>
                <a href="<?php echo $this->view->sitemapPageUrl($sitemapPage['id']); ?>">
                    <?php echo $this->view->escape($sitemapPage['short_title']);?></a>
            
            </li>
            <?php }?>
            <li>
                <a href="<?php echo $this->view->baseUrl('/admin_session/login'); ?>"><i class="fa fa-user"></i> Login</a>
            </li>
        </ul>


        <?php
        $this->view->placeholder('topMenuHtml')->captureEnd();
        return $this->view->placeholder('topMenuHtml')->toString();
    }

}
