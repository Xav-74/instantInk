<?php

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('instantInk');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>

<div class="row row-overflow">

	<div class="col-xs-12 eqLogicThumbnailDisplay">
		
		<div class="row">

			<div class="col-xs-12">
				<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
				<div class="eqLogicThumbnailContainer">
					
					<!--<div class="cursor eqLogicAction logoPrimary" data-action="add">
						<i class="fas fa-plus-circle"></i>
						<br>
						<span>{{Ajouter}}</span>
					</div>-->

					<div class="cursor logoSecondary" id="bt_sync" style="color:#3a5a7a">
						<i class="fas fa-sync"></i>
						<br>
						<span style="color:#3a5a7a">{{Synchroniser}}</span>
					</div>
					
					<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
						<i class="fas fa-wrench"></i>
						<br>
						<span>{{Configuration}}</span>
					</div>
										
					<!--Bouton Community-->
					<?php
						// uniquement si on est en version 4.4 ou supérieur
						$jeedomVersion  = jeedom::version() ?? '0';
						$displayInfoValue = version_compare($jeedomVersion, '4.4.0', '>=');
						if ($displayInfoValue) {
							echo '<div class="cursor eqLogicAction warning" data-action="createCommunityPost" title="{{Ouvrir une demande d\'aide sur le forum communautaire}}">';
							echo '<i class="fas fa-ambulance"></i>';
							echo '<span>{{Community}}</span>';
							echo '</div>';
						}
					?>
				
				</div>
			</div>

		</div>

		<legend><i class="fas fa-print"></i> {{Mes imprimantes}}</legend>
		<div class="input-group" style="margin-bottom:5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
			<div class="input-group-btn" style="margin-bottom:5px;">
				<a id="bt_resetObjectSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>
				<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
			</div>
		</div>	
		<div class="eqLogicThumbnailContainer">
			<?php
				if (count($eqLogics) == 0) {
					echo '<br><br><br><br><div class="text-center" style="padding-left:15px;"><br>{{ Aucune imprimante trouvée, cliquer sur "Synchroniser" pour commencer}}</div>';
				}
				else {				
					foreach ($eqLogics as $eqLogic)	{
						$dir = dirname(__FILE__).'/../../data/';
						$filename = $dir.$eqLogic->getConfiguration('printerId').'.png';
						$img = $eqLogic->getConfiguration('printerId').'.png';
						$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
						echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
						if ( file_exists($filename) ) { echo '<img id="img_eq" src="/plugins/instantInk/data/'.$img.'"/>'; }
						else { echo '<img id="img_eq" src="' . $plugin->getPathImgIcon() . '" />'; }
						echo '<br>';
						echo '<div class="name" style="line-height:20px !important">' . $eqLogic->getHumanName(true, true) . '</div>';
						echo '</div>';
					}
				}
			?>
		</div>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Imprimante}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br>
                           			
				<div class="row">
					
					<div class="col-sm-6">  
						<form class="form-horizontal">
							<fieldset>
						 		
								<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
								
								<div class="form-group">
									<label class="col-sm-6 control-label">{{Nom de l'équipement}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
									</div>
								</div>
							
								<div class="form-group">
									<label class="col-sm-6 control-label" >{{Objet parent}}</label>
									<div class="col-sm-6">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucun}}</option>
											<?php
											$options = '';
											foreach ((jeeObject::buildTree(null, false)) as $object) {
												$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
											}
											echo $options;
											?>
										</select>
									</div>
								</div>
									
								<div class="form-group">
									<label class="col-sm-6 control-label">{{Catégorie}}</label>
									<div class="col-sm-6">
										<?php
										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
										}
										?>
									</div>
								</div>
                        
								<div class="form-group">
								<label class="col-sm-6 control-label"></label>
									<div class="col-sm-6">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
									</div>
								</div>
							
								<br> 
                        
 							</fieldset>
						</form>
					</div>

					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>	
                        		
								<legend><i class="fas fa-info"></i> {{Informations}}</legend>
								<div id="info" class="form-group">
									<label class="col-sm-4 control-label">{{Commentaire}}</label>
									<div class="col-sm-6">
										<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
									</div>
								</div>
																							
							</fieldset>
						</form>  
                    </div>

				</div>	
					 
				<div class="row">
					<div class="col-sm-6">  
						<form class="form-horizontal">
							<fieldset>    
								
								<legend><i class="fas fa-cogs"></i> {{Paramètres de l'imprimante}}</legend>
								<div id="div_user" class="form-group">						
									<label class="col-sm-6 control-label">{{Adresse IP locale}}</label>
									<div id="div_id" class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="IPaddress" placeholder="192.168.x.x / 10.x.x.x" value="">
									</div>
								</div>
								<div id="div_user" class="form-group">						
									<label class="col-sm-6 control-label">ID</label>
									<div id="div_id" class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="printerId" placeholder="" value="" readonly>
									</div>
								</div>
								<div class="form-group">		
									<label class="col-sm-6 control-label">{{Modèle}}</label>
									<div id="div_model" class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="model" placeholder="Modèle de l'imprimante" value="" readonly>
									</div>
								</div>
								<div class="form-group">		
									<label class="col-sm-6 control-label">{{Nom}}</label>
									<div id="div_name" class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="name" placeholder="Nom de l'imprimante" value="" readonly>
									</div>
								</div>
								
								<br>

								<legend><i class="fas fa-camera"></i> {{Image}}</legend>
								<div class="form-group">
									<label class="col-sm-6 control-label"></label>
									<div id="div_img" class="col-sm-6" style="margin-bottom: 10px">
										<img id="printer_img" style="margin-top: -75px;" src=""/>
									</div>
								</div>
																							
							</fieldset>
						</form>  
                    </div>
										
				</div>
							
			</div>
						
			<style>
			</style>			
						
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!--<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br><br>-->
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{ID}}</th><th>{{Nom}}</th><th>{{Type}}</th><th>{{Logical ID}}</th><th>{{Options}}</th><th>{{Valeur}}</th><th>{{Action}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			
		</div>
	</div>

</div>


<?php include_file('desktop', 'instantInk', 'js', 'instantInk');?>
<?php include_file('core', 'plugin.template', 'js');?>