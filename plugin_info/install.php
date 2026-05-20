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

function instantInk_install() {

	// Création du cron avec valeur par défaut
    $cron = cron::byClassAndFunction('instantInk', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('instantInk');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('0 * * * *');
        $cron->setTimeout(5);
        $cron->save();
        log::add('instantInk', 'debug', 'Create cron pull');
    }

    message::add('instantInk', 'Merci pour l\'installation du plugin HP instantInk. Lisez bien la documentation avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !');
	
}

function instantInk_update() {

	// Mise à jour du cron
    $cron = cron::byClassAndFunction('instantInk', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('instantInk');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('0 * * * *');
        $cron->setTimeout(5);
        $cron->save();
        log::add('instantInk', 'debug', 'Update cron pull');
    }

    // Mise à jour de l'ensemble des commandes pour chaque équipement
    log::add('instantInk', 'debug', 'Mise à jour en cours des commandes du plugin instantInk');
    foreach (eqLogic::byType('instantInk') as $eqLogic) {
        $eqLogic->save();
        log::add('instantInk', 'debug', 'Mise à jour des commandes effectuée pour l\'équipement '. $eqLogic->getHumanName());
    }
	message::add('instantInk', 'Merci pour la mise à jour du plugin HP instantInk. Consultez les notes de version avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !');
	
 }

function instantInk_remove() {

	// Suppression du cron
    $cron = cron::byClassAndFunction('instantInk', 'pull');
    if (is_object($cron)) {
        $cron->remove();
        log::add('instantInk', 'debug', 'Remove cron pull');
    }

    message::add('instantInk', 'Le plugin HP instantInk a été correctement désinstallé. N\'hésitez pas à laisser un avis sur le Market Jeedom !');

}

?>
