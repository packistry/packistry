<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Rector\CodingStyle\Rector\Ternary\TernaryConditionVariableAssignmentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\If_\ReduceAlwaysFalseIfOrRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use RectorLaravel\Rector\Class_\ModelCastsPropertyToCastsMethodRector;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Rector\ClassMethod\MigrateToSimplifiedAttributeRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\If_\AbortIfRector;
use RectorLaravel\Rector\MethodCall\AssertStatusToAssertMethodRector;
use RectorLaravel\Rector\MethodCall\EloquentWhereRelationTypeHintingParameterRector;
use RectorLaravel\Rector\MethodCall\EloquentWhereTypeHintClosureParameterRector;
use RectorLaravel\Rector\MethodCall\ValidationRuleArrayStringValueToArrayRector;
use RectorLaravel\Rector\PropertyFetch\ReplaceFakerInstanceWithHelperRector;
use RectorLaravel\Rector\StaticCall\DispatchToHelperFunctionsRector;
use RectorLaravel\Rector\StaticCall\EloquentMagicMethodToQueryBuilderRector;
use RectorLaravel\Rector\StaticCall\RouteActionCallableRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/bootstrap/providers.php',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/routes',
        __DIR__.'/tests',
        __DIR__.'/database',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        typeDeclarations: true,
    )
    ->withRules([
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        ChangeNestedForeachIfsToEarlyContinueRector::class,
        ChangeNestedIfsToEarlyReturnRector::class,
        PreparedValueToEarlyReturnRector::class,
        RemoveAlwaysElseRector::class,
        ReturnEarlyIfVariableRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        ArraySpreadInsteadOfArrayMergeRector::class,

        NewlineAfterStatementRector::class,
        NewlineBeforeNewAssignSetRector::class,
        SymplifyQuoteEscapeRector::class,
        TernaryConditionVariableAssignmentRector::class,
        UseClassKeywordForClassNameResolutionRector::class,
        RecastingRemovalRector::class,
        ReduceAlwaysFalseIfOrRector::class,
        RemoveAlwaysTrueIfConditionRector::class,
        RemoveDeadConditionAboveReturnRector::class,

        DeclareStrictTypesRector::class,

        AddGenericReturnTypeToRelationsRector::class,
        AssertStatusToAssertMethodRector::class,
        DispatchToHelperFunctionsRector::class,
        EloquentMagicMethodToQueryBuilderRector::class,
        EloquentWhereRelationTypeHintingParameterRector::class,
        EloquentWhereTypeHintClosureParameterRector::class,

        MigrateToSimplifiedAttributeRector::class,
        ModelCastsPropertyToCastsMethodRector::class,
        RemoveDumpDataDeadCodeRector::class,
        ReplaceFakerInstanceWithHelperRector::class,
        RouteActionCallableRector::class,
        ValidationRuleArrayStringValueToArrayRector::class,
    ]);
