<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Str;

class TarantoolGrammar extends Grammar
{
    /**
     * @var array
     */
    protected array $reservedWords = [
        'migration',
        'batch',
        'exists',
        //all,alter,analyze,and,any,as,asc,asensitive,begin,between,binary,by,call,case,char,character,check,collate,column,commit,condition,connect,constraint,create,cross,current,current_date,current_time,current_timestamp,current_user,cursor,date,decimal,declare,default,delete,dense_rank,desc,describe,deterministic,distinct,double,drop,each,else,elseif,end,escape,except,exists,explain,fetch,float,for,foreign,from,function,get,grant,group,having,if,immediate,in,index,inner,inout,insensitive,insert,integer,intersect,into,is,iterate,join,leave,left,like,localtime,localtimestamp,loop,match,natural,not,null,of,on,or,order,out,outer,over,partition,pragma,precision,primary,procedure,range,rank,reads,recursive,references,reindex,release,rename,repeat,replace,resignal,return,revoke,right,rollback,row,row_number,rows,savepoint,select,sensitive,set,signal,smallint,specific,sql,start,system,table,then,to,transaction,trigger,union,unique,update,user,using,values,varchar,view,when,whenever,where,while,with
    ];

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     *
     * @return string
     */
    public function columnizeCustom(array $columns): string
    {
        $wrappedColumns = array_map([$this, 'wrap'], $columns);
        array_walk($wrappedColumns, function (&$x) {
            $x = Str::contains($x, '"') ? $x : '"' . $x . '"';
        });

        return implode(', ', $wrappedColumns);
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  $query
     * @param array $values
     *
     * @return string
     */
    public function compileInsert($query, array $values): string
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnizeCustom(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings, so we will loop through the record and parameterize them all.
        $parameters = collect($values)->map(function ($record) {
            return '(' . $this->parameterize($record) . ')';
        })->implode(', ');

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * overrides default wrapUnion function with removing parentheses on union subquery
     * that is how tarantool union works
     *
     * @param string $sql
     *
     * @return string
     */
    protected function wrapUnion($sql): string
    {
        return $sql;
    }
}
