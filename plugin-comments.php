<?php 

echo '<style>
        #c2g_comments{
            z-index:1000;
            position:fixed;
            right:50px;
            bottom:0px;
            background:white;
            box-shadow:1px 1px 2px #999; 
        }
        #c2g_comments_iframe_container{
            min-width:300px;
            overflow:visible; 
            display:none;
            background:white url(' .plugins_url('images/loader_2.gif', __FILE__). ') center no-repeat;
            border-color:#069;
            border-style:solid;
            border-width:0px 2px;
        }
        #c2g_comments_form_iframe{
            border:0px;
            width:100%;
            height:210px;
            overflow:visible;
        }
        #c2g_comments_btn{
            padding:6px 8px;
            background:white;
            color:#585858;
            font-size:1.3em;
            border-color:#585858;
            border-style:solid;
            border-width:1px 1px 0px 1px;
        }
        #c2g_comments_title{
            display:none;
            padding:13px 7px;
            background:#069; 
            color:white;
            font-weight:bold;
        }
        </style>
        <script language="javascript">
		jQuery(document).ready(function(){
			jQuery(\'#c2g_comments\').click(function(){
				var commentsUrl = \'https://app.crm2go.net/crm2go/landing?iframe=1&formid=comentarios_y_sugerencias&nolabels=1&nomargins=1&email1=' .((get_option('c2g_wcc_crm2go_email', false) !== false) ? get_option('c2g_wcc_crm2go_email', false) : 'wordpress@crm2go.net'). '&u_sistema=crm2go_wp_woocomerce_plugin\';
					jQuery(\'#c2g_comments_iframe_container, #c2g_comments_btn, #c2g_comments_title\').slideToggle();
					jQuery(\'#c2g_comments_form_iframe\').attr(\'src\',commentsUrl);
				});
		});
        </script>
        <div id="c2g_comments">
        	<div id="c2g_comments_btn"><img src="' .plugins_url('images/comments.png', __FILE__). '" /></div>
        	<div id="c2g_comments_title">&iquest;Tienes alguna sugerencia para este plugin? 
        		<span class="fa fa-close" style="float:right;margin:4px 10px 0px 0px;"></span>
			</div>
        	<div id="c2g_comments_iframe_container">
        		<iframe id="c2g_comments_form_iframe" border="0" style="" src="about:blank"></iframe>
        	</div>
        </div>';

?>