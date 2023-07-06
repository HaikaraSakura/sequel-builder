# sequel-builder

SQL文の組み立てをおこなうライブラリ。  
プレースホルダーを含むSQL文を組み立て、埋め込む値を保持する。  
SQLの文法に近い書き心地を目標とする。

## 利用方法

### ビルダーのインスタンス化と、SQL文の取得

基本クラスとして、Select、Insert、Update、Deleteが存在する。  
いずれもBuilderクラスを継承しており、以下のように用いる。

```PHP
// コンストラクタの引数にテーブル名を渡す。
$builder = SQL::select('items');

 // SQL文の組み立てを実行
$builder->build();

// SELECT items.* FROM items という文字列を取得できる。
$builder->getQuery();
```

### カラムの指定

SELECT文で取得するカラムを指定したい場合、columnsメソッドに可変長引数で指定する。

```PHP
// メソッドチェーンで記述する場合、PHPの構文上、インスタンス化部分を()で囲む必要がる。
$builder = SQL::select('items')
    ->columns('id', 'item_code', 'item_name', 'category_id');

/*
    SELECT
        id, item_code, item_name, category_id
    FROM items
*/
```

### WHERE

WHERE句による絞り込みは、whereメソッドにcallableな値を渡す形で記述する。  
callable値の実行、引数にRulesクラスのインスタンスが渡されるので、必要なメソッドを呼び出したうえでreturnする。

Rulesクラスに備わるメソッドは様々なので、以下では簡易な例を紹介する。

```PHP
// item_codeが00001で、deleted_flagがNULLではないレコードを抽出
$builder = SQL::select('items')
    ->where(fn (Rules $rules) => $rules
        ->equals('item_code', '00001')
        ->isNotNull('deleted_flag'));

/*
    SELECT items.*
    FROM items
    WHERE
        item_code = ?
        AND deleted_flag IS NOT NULL
*/
```

### テーブルの結合

LEFT JOINなど、テーブル結合の記述。  
ON句での結合条件の記述は、WHEREと同じくcallableとRulesクラスを用いる。

```PHP
/* equalsの第二引数に、テーブル名.カラム名の文字列をそのまま渡すと、エスケープされてしまい、
テーブル名.カラム名として扱われなくなってしまうので、Rawクラスを渡す必要がある。 */
$builder = SQL::select('items')
    ->columns('items.id', 'items.item_code', 'items.item_name', 'categories.category_name')
    ->leftJoin('categories', fn (Rules $rules) => $rules
        ->equals('categories.id', new Raw('items.category_id')))

/*
    SELECT
        items.id, items.item_code, items.item_name, categories.category_name
    FROM items
    LEFT JOIN categories
        ON categories.id = items.category_id
*/
```

### GROUP BYとHAVING

HAVINGの使い方はWHEREとまったく同じになる。

```PHP
// 取引先ごとの月間売上テーブルを検索。年間の売上が1000000以上の取引先、という条件で絞り込む。
// customer_code = 取引先コード、amount = 売上金額とする。
$builder = SQL::select('monthly_sales')
    ->columns('customer_code', 'SUM(amount) AS yearly_sales')
    ->groupBy('customer_code')
    ->having(fn (Rules $rules) => $rules
        ->compare('yearly_sales', '>=', 1000000));

/*
    SELECT
        customer_code, SUM(amount) AS yearly_sales
    FROM monthly_sales
    GROUP BY customer_code
    HAVING
        yearly_sales >= 1000000
*/
```

### ORDER BY

```PHP
// 月間売上テーブルを集計し、年間売上の多い順に取得
$builder = SQL::select('monthly_sales')
    ->columns('customer_code', 'SUM(amount) AS yearly_sales')
    ->groupBy('customer_code')
    ->orderByDesc('yearly_sales');

/*
    SELECT
        customer_code, SUM(amount) AS yearly_sales
    FROM monthly_sales
    GROUP BY customer_code
    ORDER BY yearly_sales DESC
    */
```

### LIMITとOFFSET

limitメソッドとoffsetメソッドがあり、別々に指定できるが、  
OFFSET値は、limitの第二引数にまとめて渡すこともできる。

```PHP
// 月間売上テーブルを集計し、年間売上の多い順に取得
$current_page = 5;
$builder = SQL::select('items')
    ->limit(25)
    ->offset(25 * ($current_page - 1));

/*
    SELECT items.*
    FROM items
    LIMIT 25 OFFSET 100
*/

$builder = SQL::select('items')
    ->limit(25, 25 * ($current_page - 1));

/*
    SELECT items.*
    FROM items
    LIMIT 25, 100
*/
```

## Rulesクラスの詳細

whereメソッドなどで用いるRulesクラスのメソッドを詳しく紹介する。

### 条件分岐

whenメソッドで条件分岐を記述できる。これはSQLの構文ではなく、  
PHPのif文によって動的に検索条件を組み変える際の、より良い代替手段として用意されている。

whenの第一引数に条件式（bool値）を渡し、第二引数にcallableを渡す。

```PHP

// ユーザーが検索フォームで入力した値
$_GET = [
    'item_name' => '商品名',
    'category_id' => ''
];

$builder = SQL::select('items')
    ->where(fn (Rules $rules) => $rules
        ->isNotNull('deleted_flag')
        ->when(
            isset($_GET['name']) && $_GET['item_name'] !== '', // この式がtrueだった場合、第二引数が有効になり、WHERE句に追記される
            fn (Rules $rules) => $rules->like('item_name', $_GET['item_name'])
        )
        ->when(
            isset($_GET['category_id']) && $_GET['category_id'] !== '',
            fn (Rules $rules) => $rules->equals('category_id', $_GET['category_id'])
        ));

/*
    category_idは条件を満たさないので含まれない

    SELECT *
    FRMO items
    WHERE
        deleted_flag IS NOT NULL
        AND item_name LIKNE ?
*/
```

### OR

Rulesクラスの同じオブジェクトに対して指定した条件は、すべて並列にANDで繋がれる。  
ORを書きたい場合は、anyメソッドを用いて、別のRulesオブジェクトに指定する形になる。Rulesのネスト。

```PHP
// category_idが1か2の商品情報を取得する

$builder = SQL::select('items')
    ->where(fn (Rules $rules) => $rules
        ->isNotNull('deleted_flag')
        ->any(fn (Rules $rules) => $rules
            ->equals('category_id', 1)
            ->equals('category_id', 2)));

/*
    SELECT items.*
    FROM items
    WHERE
        deleted_flag IS NOT NULL
        AND (
            category_id = ?
            OR category_id = ?
        )
*/
```

### INとサブクエリ

```PHP

$builder = SQL::select('items')
    ->where(fn (Rules $rules) => $rules
        ->isNotNull('deleted_flag')
        ->in('category_id', [1, 2]));

/*
    SELECT items.*
    FROM items
    WHERE
        deleted_flag IS NOT NULL
        AND category_id IN (?, ?)
*/
```

inメソッドの第二引数に配列ではなく、Selectクラスのオブジェクトを渡すことで、サブクエリを用いた絞り込みができる。

```PHP
// 何らかの申請情報を持つrequestsテーブルと、承認された申請のidを保持するapproved_requestsテーブルを想定。
// サブクエリを用いて、承認済みの申請の情報のみを取得する。

$builder = SQL::select('requests')
    ->where(fn (Rules $rules) => $rules
        ->in('id', (SQL::select('approved_requests'))->columns('request_id')));

/*
    SELECT requests.*
    FROM requests
    WHERE
        id IN (SELECT request_id FROM approved_requests)
*/
```

## CASE文

Case文を記述するには、いくつかパターンがある。

下記のようなクエリの組み立てを想定する。

```SQL
SELECT id,
       category_name,
       CASE
           WHEN id = ? THEN 'selected'
           ELSE ''
           END AS selected
FROM categories
```

### CASE文の文字列を渡す例

SQLインジェクションの危険があるので、これは絶対にNG。

```PHP
$builder = SQL::select('categories')
    ->columns(
        'id',
        'category_name',
        "CASE WHEN id = {$post['category_id']} THEN 'selected' ELSE '' END AS selected"
    );
```

### CaseStatementを用いてCASE文を記述する例

ビルダーの機能だけで組み立てるので安全だが、可読性に難がある。

```PHP
$builder = SQL::select('categories')
    ->columns(
        'id',
        'category_name',
        [
            SQL::case()
                ->whenThen(SQL::rules()->equals('id', $post['category_id']), 'selected')
                ->else(''),
            SQL::raw('selected')
        ]
    );
```

### プレースホルダを含むCASE文の文字列を渡すパターン

名前付きプレースホルダで記述した場合でも、内部で?のプレースホルダに変換されるので注意。  
下記の例では:idが?になる。

```PHP
$builder = SQL::select('categories')
    ->columns(
        'id',
        'category_name',
        SQL::raw("CASE WHEN id = :id THEN 'selected' ELSE '' END AS selected")
            ->bindValue('id', $post['category_id']) // 値をバインド
    );
```

## INSERT

### 通常のINSERT文

```PHP
// 取引先ごとの月間予算を保持するmonthly_budgetsテーブルを想定
$query = SQL::insert('monthly_budgets')
    ->value('customer_code', '00001')
    ->value('year', 2022)
    ->value('month', 1)
    ->value('amount', 300000);

/*
    INSERT INTO monthly_budgets (
        customer_code, year, month, amount
    ) VALUES (
        ?, ?, ?, ?
    )
*/
```

### ON DUPLICATE KEY UPDATE

ユニークキーなどの制約に引っかかることでINSERTに失敗したら、代わりにUPDATEを実行したい場合がある。  
SELECTでの存在チェックの結果をもとに、PHP側で条件分岐を書いてもいいが、ON DUPLICATE KEY UPDATEを用いる方法もある。  
これにより「あればINSERT，なければUPDATE」の動きを、ひとつのSQLで済ませることができる。

多対多の中間テーブルに対して繰り返し登録・更新をおこなうような処理で、役に立つかもしれない。

```PHP
// customer_code、year、monthの3カラムで複合ユニーク制約をかけている前提。
// 制約に引っかかったら、同じ値でのUPDATEを実行する。
$query = SQL::insert('monthly_budgets')
    ->value('customer_code', '00001')
    ->value('year', 2022)
    ->value('month', 1)
    ->value('amount', 300000)
    ->onDuplicateKeyUpdate();

/*
    INSERT INTO monthly_budgets (
        customer_code, year, month, amount
    ) VALUES (
        ?, ?, ?, ?
    ) ON DUPLICATE KEY UPDATE (
        customer_code = ?, year = ?, month = ?, amount = ?
    )
*/
```

## UPDATE

UPDATEの条件はSELECTと同様、whereメソッドにcallableを渡し、Rulesクラスを用いる。

```PHP
$query = SQL::update('items')
    ->set('item_name', '商品3')
    ->set('category_id', 2)
    ->where(fn (Rules $rules) => $rules
        ->isNotNull('deleted_flag')
        ->equals('id', 1));

/*
    UPDATE items
    SET
        item_name = ?,
        category_id = ?
    WHERE
        deleted_flag IS NOT NULL
        AND id = ?
*/
```

### CASE文を用いた一括UPDATE

```PHP
// ある取引先の月別予算を一括で更新する

$query = SQL::update('monthly_budgets')
    ->value('amount', SQL::raw(<<< SQL
        CASE `month`
            WHEN 1 THEN ?
            WHEN 2 THEN ?
            WHEN 3 THEN ?
            WHEN 4 THEN ?
            WHEN 5 THEN ?
            WHEN 6 THEN ?
            WHEN 7 THEN ?
            WHEN 8 THEN ?
            WHEN 9 THEN ?
            WHEN 10 THEN ?
            WHEN 11 THEN ?
            WHEN 12 THEN ?
        END
    SQL)
        ->bindValues([
            1000, 1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900, 2000, 2100
        ]))
    ->where(fn (Rules $rules) => $rules
        ->equals('customer_code', '00001')
        ->equals('year', 2022)
    );

/*
    UPDATE monthly_budgets
    SET
        amount = CASE `month`
            WHEN 1 THEN ?
            WHEN 2 THEN ?
            WHEN 3 THEN ?
            WHEN 4 THEN ?
            WHEN 5 THEN ?
            WHEN 6 THEN ?
            WHEN 7 THEN ?
            WHEN 8 THEN ?
            WHEN 9 THEN ?
            WHEN 10 THEN ?
            WHEN 11 THEN ?
            WHEN 12 THEN ?
        END
    WHERE
        customer_code = ?
        AND year = ?
*/
```

## DELETE

DELETEの条件も、whereメソッドにcallableを渡し、Rulesクラスを用いる。

```PHP
$query = SQL::delete('approved_requests')
    ->where(fn (Rules $rules) => $rules
        ->equals('id', 1));

/*
    DELETE FROM approved_requests
    WHERE id = ?
*/
```