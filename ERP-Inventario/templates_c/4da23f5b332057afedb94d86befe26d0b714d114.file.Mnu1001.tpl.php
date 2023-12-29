<?php /* Smarty version Smarty-3.1.8, created on 2023-12-14 17:08:34
         compiled from "Plantillas/Mnu1001.tpl" */ ?>
<?php /*%%SmartyHeaderCode:1136051599657b28828741e0-19127307%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '4da23f5b332057afedb94d86befe26d0b714d114' => 
    array (
      0 => 'Plantillas/Mnu1001.tpl',
      1 => 1695224494,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1136051599657b28828741e0-19127307',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'saDatos' => 0,
    'lcCodRol' => 0,
    'i' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_657b288287e3b3_87614523',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_657b288287e3b3_87614523')) {function content_657b288287e3b3_87614523($_smarty_tpl) {?><?php $_smarty_tpl->tpl_vars['lcCodRol'] = new Smarty_variable('*', null, 0);?>    
<?php  $_smarty_tpl->tpl_vars['i'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['i']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['saDatos']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['i']->key => $_smarty_tpl->tpl_vars['i']->value){
$_smarty_tpl->tpl_vars['i']->_loop = true;
?>  
    <?php if ($_smarty_tpl->tpl_vars['lcCodRol']->value!=$_smarty_tpl->tpl_vars['i']->value['CCODROL']){?>
        <div class="col-12">
            <div class="page-header">
                <h2 class="main-content-title tx-2 mg-b-2"><?php echo $_smarty_tpl->tpl_vars['i']->value['CDESROL'];?>
</h2>
            </div>
        </div>
    <?php }?>
    <div class="col-xs-12 col-sm-6 col-md-4 col-xl-3 col-lg-6">
        <div class="card custom-card text-center">   
            <div class="card-body dash1 text-center">
                <div><a href="<?php echo $_smarty_tpl->tpl_vars['i']->value['CCODOPC'];?>
.php"><img src="img/MenuImagen/<?php echo $_smarty_tpl->tpl_vars['i']->value['CIMAGE'];?>
" width="80" height="80"></a></div>
                <br>
                <div><h4><a href="<?php echo $_smarty_tpl->tpl_vars['i']->value['CCODOPC'];?>
.php"><?php echo $_smarty_tpl->tpl_vars['i']->value['CDESOPC'];?>
</a></h4></div>
                <div class="progress mb-1">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                        class="progress-bar progress-bar-xs wd-100p bg-success"
                        role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
<?php $_smarty_tpl->tpl_vars['lcCodRol'] = new Smarty_variable($_smarty_tpl->tpl_vars['i']->value['CCODROL'], null, 0);?>
<?php } ?> <?php }} ?>