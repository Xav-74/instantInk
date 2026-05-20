<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<form class="form-horizontal">
    
    <fieldset>

        <legend><i class="fas fa-key"></i> {{Authentification HP Smart}}</legend>
        <div class="form-group">
            <label class="col-sm-2 control-label">{{Statut}}</label>
            <div class="col-sm-4">
                <span id="token_status" class="label label-default">{{Chargement...}}</span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Session Id</label>
            <div class="col-sm-4">
                <input class="configKey form-control" data-l1key="sessionId" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label"></label>
            <div class="col-sm-4">
                <a class="btn btn-primary btn-sm" id="bt_connect"><i class="fas fa-plug"></i> {{Connexion}}</a>
                <a class="btn btn-default btn-sm" id="bt_howto" data-toggle="collapse" href="#howto" onclick="this.blur();"><i class="fas fa-question-circle"></i> {{Comment récupérer le session Id ?}}</a>
                <a class="btn btn-danger btn-sm" id="bt_resetTokens"><i class="far fa-trash-alt"></i> {{Suppression tokens}}</a>
            </div>
            </br></br>
            <div class="collapse" id="howto">
                <div class="col-sm-offset-2 col-sm-4">
                    <div class="" style="font-size:.9em; line-height:16px">
                        <ol>
                            <li>{{Ouvrez}} <a href="https://portal.hpsmart.com" target="_blank">portal.hpsmart.com</a>
                                {{dans votre navigateur et connectez-vous}}</li>
                            <li>{{Appuyez sur}} <kbd>F12</kbd> {{pour ouvrir les DevTools}}</li>
                            <li>{{Allez dans l'onglet}} <kbd>{{Application}}</kbd>
                                (Chrome) {{ou}} <kbd>{{Stockage}}</kbd> (Firefox)</li>
                            <li>{{Dans le panneau latéral :}} <kbd>Cookies</kbd>
                                → <kbd>https://portal.hpsmart.com</kbd></li>
                            <li>{{Trouvez le cookie}} <kbd>shell-session-id</kbd>
                                {{et copiez sa valeur complète.}}</li>
                            <li>{{Collez-la dans le champ ci-dessus et cliquez sur}}
                                <kbd>{{Connexion}}</kbd></li>
                        </ol>
                        <p>
                            <i class="fas fa-clock"></i> {{Ce token est valable 90 jours. Jeedom vous avertira quand il faudra le renouveler}}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <br/>

        <legend><i class="fas fa-sync"></i> {{Rafraîchissement automatique}}</legend>
        <div class="form-group pull_class">
            <label class="col-sm-2 control-label">{{Cron personalisé}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Minimum 1h conseillé}}"></i></sup>
            </label>
            <div class="col-sm-4">
                <div class="input-group">
                    <input id="cronPattern" class="form-control configKey" data-l1key="cronPattern" placeholder="0 * * * *"/>
                    <span class="input-group-btn">
                        <a class="btn btn-primary jeeHelper" data-helper="cron" style="width:32px;" title="{{Assistant cron}}"><i class="fas fa-question-circle"></i></a>
                    </span>
                </div>
            </div>
        </div>
        </br></br>
 
    </fieldset>

</form>

<script>

    var CommunityButton = document.querySelector('#createCommunityPost > span');
    if(CommunityButton) {CommunityButton.innerHTML = "{{Community}}";}

    /* Fonction permettant la modification du cron */
    document.getElementById('bt_savePluginConfig').addEventListener('click', function() {
        scheduleCron();
    });
    
    function scheduleCron()  {
        
        var cronPattern = document.getElementById('cronPattern').value;
        if (cronPattern == null || cronPattern == '') { cronPattern = '0 * * * *'; }        
        
        $.ajax({
            type: "POST",
            url: "plugins/instantInk/core/ajax/instantInk.ajax.php",
            data: {
                action: "scheduleCron",
                cronPattern: cronPattern,
                },
            dataType: 'json',
                error: function (request, status, error) {
                handleAjaxError(request, status, error);
                },
            success: function (data) { 			

                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: '{{Error ! Cron not updated}}'+' ('+cronPattern+')', level: 'danger'});
                    return;
                }
                else  {
                    $('#div_alert').showAlert({message: '{{Cron updated}}'+' ('+cronPattern+')', level: 'success'});                    
                }
            }
        });
    };

    $(document).ready(function () {

        // Connection status
        $.ajax({
            type: 'POST',
            url: 'plugins/instantInk/core/ajax/instantInk.ajax.php',
            data: {
                action: 'getConnectionStatus'
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function(data) {
                if (data.state !== 'ok') {
                    $('#div_alert').showAlert({message: '{{Erreur lors de la récupération du statut du token}}', level: 'danger'});
                    return;
                }
                else {
                    var $el = $('#token_status');
                    var res = data.result;
                    if (!res.has_session) {
                        $el.removeClass().addClass('label label-danger').text('{{Non connecté}}');
                    } else if (res.session_expired) {
                        $el.removeClass().addClass('label label-warning').text('{{Session expirée — renouvelez le session Id}}');
                    } else if (res.token_expired) {
                        $el.removeClass().addClass('label label-warning').text('{{Access token expiré — sera renouvelé automatiquement}}');
                    } else {
                        $el.removeClass().addClass('label label-success').text('{{Connecté — token valide jusqu\'au}} ' + res.token_expires);
                    }
                }
            }
        });

        // Connection
        $('#bt_connect').on('click', function () {
            
            document.getElementById('bt_savePluginConfig').click();
            var $el = $('#token_status');
            $el.removeClass().addClass('label label-info').text('{{Test en cours..}}');
            
            $.ajax({
                type: 'POST',
                url: 'plugins/instantInk/core/ajax/instantInk.ajax.php',
                data: {
                    action: 'connection'
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) {
                    if (data.state === 'ok') {
                        var res = data.result;
                        $el.removeClass().addClass('label label-success').text('{{Connexion réussie ! }}' + '{{Compte : }}' + res.email + ' (' + res.firstName + ' ' + res.lastName + ')');
                    } else {
                        $el.removeClass().addClass('label label-danger').text('{{Erreur}} : ' + data.error);
                        return;
                    }
                }            
            });
        });

        // Reset tokens
        $('#bt_resetTokens').on('click', function () {
            
            var $el = $('#token_status');
                        
            $.ajax({
                type: 'POST',
                url: 'plugins/instantInk/core/ajax/instantInk.ajax.php',
                data: {
                    action: 'resetTokens'
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) {
                    if (data.state === 'ok' && data.result['res'] == "OK") {
                        $el.removeClass().addClass('label label-danger').text('{{Non connecté}}');
                    } else {
                        $el.removeClass().addClass('label label-danger').text('{{Erreur}} : ' + data.error);
                        return;
                    }
                }            
            });
        });
    });

</script>
