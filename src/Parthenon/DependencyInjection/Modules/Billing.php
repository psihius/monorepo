<?php

declare(strict_types=1);

/*
 * Copyright Iain Cambridge 2020-2022.
 *
 * Use of this software is governed by the Business Source License included in the LICENSE file and at https://getparthenon.com/docs/next/license.
 *
 * Change Date: TBD ( 3 years after 2.2.0 release )
 *
 * On the date above, in accordance with the Business Source License, use of this software will be governed by the open source license specified in the LICENSE file.
 */

namespace Parthenon\DependencyInjection\Modules;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Billing implements ModuleConfigurationInterface
{
    public function addConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder->arrayNode('billing')
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ?->arrayNode('payments')
                    ->children()
                        ->scalarNode('provider')->end()
                        ?->arrayNode('adyen')
                            ->children()
                                ->scalarNode('api_key')->end()
                                ->scalarNode('merchant_account')->end()
                                ->booleanNode('pci_mode')->end()
                                ->booleanNode('test_mode')->end()
                                ->scalarNode('return_url')->end()
                                ->scalarNode('prefix')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ?->end();
    }

    public function handleDefaultParameters(ContainerBuilder $container): void
    {
        // TODO: Implement handleDefaultParameters() method.
    }

    public function handleConfiguration(array $config, ContainerBuilder $container): void
    {
        // TODO: Implement handleConfiguration() method.
    }
}
