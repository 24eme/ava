<?php
require_once dirname(__FILE__) . '/../../../bootstrap.php';

class Elastica_Query_MatchTest extends PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
    	$field = 'test';
        $testQuery = 'Nicolas Ruflin';
        $type = 'phrase';
        $operator = 'and';
        $analyzer = 'myanalyzer';
        $boost = 2.0;
        $minimumShouldMatch = 2;
        $fuzziness = 0.3;
        $fuzzyRewrite = 'constant_score_boolean';
        $prefixLength = 3;
        $maxExpansions = 12;
        
        $query = new Elastica_Query_Match();
        $query->setFieldQuery($field, $testQuery);
        $query->setFieldType($field, $type);
        $query->setFieldOperator($field, $operator);
        $query->setFieldAnalyzer($field, $analyzer);
        $query->setFieldBoost($field, $boost);
        $query->setFieldMinimumShouldMatch($field, $minimumShouldMatch);
        $query->setFieldFuzziness($field, $fuzziness);
        $query->setFieldFuzzyRewrite($field, $fuzzyRewrite);
        $query->setFieldPrefixLength($field, $prefixLength);
        $query->setFieldMaxExpansions($field, $maxExpansions);
		
        $expectedArray = array(
            'match' => array(
                $field => array(
                    'query' => $testQuery,
                    'type' => $type,
                	'operator' => $operator,
                    'analyzer' => $analyzer,
                	'boost' => $boost,
                	'minimum_should_match' => $minimumShouldMatch,
                	'fuzziness' => $fuzziness,
                	'fuzzy_rewrite' => $fuzzyRewrite,
                	'prefix_length' => $prefixLength,
                    'max_expansions' => $maxExpansions
                )
            )
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    public function testMatch()
    {
    	$client = new Elastica_Client();
    	$index = $client->getIndex('test');
    	$index->create(array(), true);
    	$type = $index->getType('test');
    
    	$doc = new Elastica_Document(1, array('name' => 'Basel-Stadt'));
    	$type->addDocument($doc);
    	$doc = new Elastica_Document(2, array('name' => 'New York'));
    	$type->addDocument($doc);
    	$doc = new Elastica_Document(3, array('name' => 'New Hampshire'));
    	$type->addDocument($doc);
    	$doc = new Elastica_Document(4, array('name' => 'Basel Land'));
    	$type->addDocument($doc);
    
    	$index->refresh();
    
    	$field = 'name';
    	$operator = 'or';
    
    	$query = new Elastica_Query_Match();
    	$query->setFieldQuery($field, 'Basel New');
    	$query->setFieldOperator($field, $operator);
    
    	$resultSet = $index->search($query);
    
    	$this->assertEquals(4, $resultSet->count());
    }    
    
    public function testMatchPhrase()
    {
        $client = new Elastica_Client();
        $index = $client->getIndex('test');
        $index->create(array(), true);
        $type = $index->getType('test');

        $doc = new Elastica_Document(1, array('name' => 'Basel-Stadt'));
        $type->addDocument($doc);
        $doc = new Elastica_Document(2, array('name' => 'New York'));
        $type->addDocument($doc);
        $doc = new Elastica_Document(3, array('name' => 'New Hampshire'));
        $type->addDocument($doc);
        $doc = new Elastica_Document(4, array('name' => 'Basel Land'));
        $type->addDocument($doc);

        $index->refresh();

        $field = 'name';
        $type = 'phrase';

        $query = new Elastica_Query_Match();
        $query->setFieldQuery($field, 'New York');
        $query->setFieldType($field, $type);

        $resultSet = $index->search($query);

        $this->assertEquals(1, $resultSet->count());
    }
    
    public function testMatchPhrasePrefix()
    {
    	$client = new Elastica_Client();
    	$index = $client->getIndex('test');
    	$index->create(array(), true);
    	$type = $index->getType('test');
    
    	$doc = new Elastica_Document(1, array('name' => 'Basel-Stadt'));
    	$type->addDocument($doc);
    	$doc = new Elastica_Document(2, array('name' => 'New York'));
    	$type->addDocument($doc);
    	$doc = new Elastica_Document(3, array('name' => 'New Hampshire'));
    	$type->addDocument($doc);
    	$doc = new Elastica_Document(4, array('name' => 'Basel Land'));
    	$type->addDocument($doc);
    
    	$index->refresh();
    
    	$field = 'name';
    	$type = 'phrase_prefix';
    
    	$query = new Elastica_Query_Match();
    	$query->setFieldQuery($field, 'New');
    	$query->setFieldType($field, $type);
    
    	$resultSet = $index->search($query);
    
    	$this->assertEquals(2, $resultSet->count());
    }    
}
