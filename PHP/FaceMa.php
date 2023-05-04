<?php

// test1 年报sql

/*SELECT
  year(OrderTime) AS year,
  month(OrderTime) AS month,
  country,
  COUNT(*) AS total_orders,
  SUM(Amount) AS total_amount
FROM
  order_table
WHERE
  year(OrderTime) = 2018
GROUP BY
  year(OrderTime),
  month(OrderTime),
  country
ORDER BY
  year(OrderTime),
  month(OrderTime);*/



// test2 产品日报和店铺日报

/**
 * Notes:
 * # 每日访问次数（PV）：产品页面的每日访问次数是日志表中产品页面ID与产品ID相同的行数。
 * # 访客数（UV）：产品页面的每日访客数是日志表中产品页面ID与产品ID相同的唯一访客ID的数量。
 * # 每页平均停留时间：产品的每页平均停留时间是在产品页面上花费的总时间除以产品页面的访问次数。
 * Date: 2023/4/24 09:33
 * @param $log_table
 * @param $product_id
 * @return array
 * @author: wangjz
 */
function calculate_product_daily_report($log_table, $product_id)
{
    // 获取日志表中产品页面 ID 与产品 ID 相同的行数。
    $number_of_visits = "select count(*) from %s where log_time > %s and product_id = %d";

    // 获取日志表中产品页面ID与产品ID相同的唯一访问者ID的数量。
    $number_of_visitors = "select count(*) from %s where log_time > %s and product_id = %d group by visitor_id";

    // 获取在产品页面上花费的总时间。产品页面ID与产品ID相同的当前记录时间与前一记录时间，使用循环相加
    $total_time_spent = "";

    // 计算每页的平均停留时间。
    $average_stay_time_per_page = $total_time_spent / $number_of_visitors;
    return [
        'number_of_visits' => $number_of_visits,
        'number_of_visitors' => $number_of_visitors,
        'average_stay_time_per_page' => $average_stay_time_per_page
    ];

}


/**
 * Notes:
 * # PV：店铺的每日访问量是日志表中store_id与要查询相同的行数。
 * # 访客数（UV）：店铺每天的访客数，是日志表中店铺ID与店铺ID相同的唯一访客ID的个数。
 * # 平均停留时间：商店的平均停留时间是在商店产品页面上花费的总时间除以访问商店的次数。
 * Date: 2023/4/24 09:33
 * @param $log_table
 * @param $product_id
 * @author: wangjz
 */
function calculate_store_daily_report($log_table, $product_id)
{
    // 获取日志表中商店 ID 与商店 ID 相同的行数。
    $number_of_visits = "select count(*) from %s where log_time > %s and store_id = %d";

    //  获取log表中store ID与store ID相同的唯一访客ID的个数。
    $number_of_visitors ="select count(*) from %s where log_time > %s and store_id = %d group by visitor_id";

    // 获取花在商店产品页面上的总时间。
    // 产品ID与店铺ID相同的当前记录时间与前一记录时间，使用循环相加
    // todo  核心问题 需优化
    $total_time_spent = "select sum() from %s where log_time > %s and store_id = %d ";

    // 计算平均停留时间
    $average_stay_time = $total_time_spent / $number_of_visits;

    return [
        'number_of_visits' => $number_of_visits,
        'number_of_visitors' => $number_of_visitors,
        'average_stay_time_per_page' => $average_stay_time
    ];
}


// test3 假设我现在有一个订单，订单上有发往的国家和包裹重量，请写出你的实现思路和算法，让订单匹配出最便宜的物流商来承接这一单

/**
 * Notes:
 * Date: 2023/4/24 09:51
 * @param array $order
 * @param array $quotation_list
 * @return mixed
 * @author: wangjz
 */
function match_order_provider(array $order, array $quotation_list)
{
    //  step1 获取可以送货到目的地国家的物流商名单
    $providers_can_deliver = array_filter($quotation_list,
        function ($logistics_provider) use ($order) {
            return $logistics_provider['country'] === $order['to_country'];
        });

    //  step2 遍历每个物流供应商，计算运费
    // 运费按包裹重量乘以单价计算。 如果包裹重量小于首重，运费按首重计算
    $shipping_costs = array();
    foreach ($providers_can_deliver as $provider) {
        if ($order['weight'] < $provider['first_weight']) {
            $shipping_cost = $provider['first_weight'] * $provider['unit_price'];
        } else {
            $shipping_cost = $order['weight'] * $provider['unit_price'];
        }
        $shipping_costs[$provider['name']] = $shipping_cost;
    }

    // step3 选择陈本最低的供应商
    return min($shipping_costs, key($shipping_costs));
}