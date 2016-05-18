<?php
/**  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */

namespace oat\taoProctoring\model\execution;

use oat\oatbox\Configurable;
use oat\taoDelivery\model\execution\DeliveryExecution as InterfaceDeliveryExecution;

/**
 * Service to manage the execution of deliveries
 *
 * @access public
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package taoProctoring
 */
class DeliveryExecutionService
    extends Configurable
    implements \taoDelivery_models_classes_execution_Service, \taoDelivery_models_classes_execution_Monitoring
{

    /**
     * @return \taoDelivery_models_classes_execution_Service
     */
    public function getImplementation() {
        return $this->getOption('implementation');
    }

    /**
     * @inheritdoc
     * @return InterfaceDeliveryExecution[]
     */
    public function getExecutionsByDelivery(\core_kernel_classes_Resource $compiled)
    {
        $executions = $this->getImplementation()->getExecutionsByDelivery($compiled);
        $result = [];
        foreach ($executions as $execution) {
            $result[] = new DeliveryExecution($execution);
        }
        return $result;
    }

    /**
     * @inheritdoc
     * @return InterfaceDeliveryExecution[]
     */
    public function getDeliveryExecutionsByStatus($userUri, $status)
    {
        $executions = $this->getImplementation()->getDeliveryExecutionsByStatus($userUri, $status);
        $result = [];
        foreach ($executions as $execution) {
            $result[] = new DeliveryExecution($execution);
        }
        return $result;
    }

    /**
     * @inheritdoc
     * @return InterfaceDeliveryExecution[]
     */
    public function getUserExecutions(\core_kernel_classes_Resource $compiled, $userUri)
    {
        $executions = $this->getImplementation()->getUserExecutions($compiled, $userUri);
        $result = [];
        foreach ($executions as $execution) {
            $result[] = new DeliveryExecution($execution);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function initDeliveryExecution(\core_kernel_classes_Resource $assembly, $userUri)
    {
        $execution = $this->getImplementation()->initDeliveryExecution($assembly, $userUri);
        return new DeliveryExecution($execution);
    }
    
    /**
     * (non-PHPdoc)
     * @see taoDelivery_models_classes_execution_Service::getDeliveryExecution()
     */
    public function getDeliveryExecution($identifier) {
        return new DeliveryExecution(
            $this->getImplementation()->getDeliveryExecution($identifier)
        );
    }

}
