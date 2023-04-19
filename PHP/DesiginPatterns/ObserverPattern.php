<?php
# 使用观察者模式实现杂志订阅系统：store 发行杂志 customer 订阅杂志
// 杂志订阅观察者
class MagazineSubscriptionObserver{
    public function onSubscribe(MagazineSubscriptionEvent $event){
        // $magazine = $event->getMagazine();
        $customer = $event->getCustomer();

        // 调用邮件类库
        $email = new \Email();
        $email->setSubject('magazine subscribe！！');
        $email->setBody('Thank you for subscribing to our magazine!');
        $email->setTo($customer->getEmail()); // 人员配置

        $mailer = new \Mailer();
        $mailer->send($email);
    }

    public function onNoSubscribe(MagazineSubscriptionEvent $event){
        // $magazine = $event->getMagazine();
        $customer = $event->getCustomer();

        $email = new \Email();
        $email->setSubject('Magazine Subscription Cancellation');
        $email->setBody('Your subscription to our magazine has been cancelled.');
        $email->setTo($customer->getEmail());

        $mailer = new \Mailer();
        $mailer->send($email);
    }
}

// 杂志订阅事件
class MagazineSubscriptionEvent{
    protected $magazine;
    protected $customer;

    public function __construct(Magazine $magazine,Customer $customer)
    {
        $this->magazine =  $magazine;
        $this->customer = $customer;
    }

    public function getMagazine(){
        return $this->magazine;
    }

    public function getCustomer(){
        return $this->customer;
    }
}

// 发行杂志

class Store{
    protected $magazines;
    protected $observers;

    public function __construct(){
        $this->magazines = new ArrayObject();
        $this->observers = new ArrayObject();
    }

    public function addMagazine(Magazine $magazine){
        $this->magazines[] = $magazine;
    }

    public function removeMagazine(Magazine $magazine){
        $this->magazines->removeElement($magazine);
    }

    public function addObserver(MagazineSubscriptionObserver $observer){
        $this->observers[] = $observer;
    }

    public function removeObserver(MagazineSubscriptionObserver $observer){
        $this->observers->removeElement($observer);
    }

    public function subscribe(Customer $customer, Magazine $magazine){
        $event=  new MagazineSubscriptionEvent($magazine,$customer);
        foreach ($this->observers as $observer){
            $observer->onSubScribe($event);
        }
    }

    public function unSubscribe(Customer $customer,Magazine $magazine){
        $event =  new MagazineSubscriptionEvent($magazine,$customer);
        foreach ($this->observers as $observer){
            $observer->onUnSubScribe($event);
        }
    }
}

// 这段代码实现了一个简单的杂志订阅系统。
//Store类代表商店，Magazine类代表杂志，Customer类代表客户，
//MagazineSubscriptionObserver类代表可以通知订阅事件的观察者，
//MagazineSubscriptionEvent类代表客户订阅时触发的事件 或取消订阅杂志。
//
//Store 类具有用于添加和删除杂志、添加和删除观察者、为客户订阅杂志以及取消客户订阅杂志的方法。
//当客户订阅或取消订阅杂志时，Store 类会触发相应的事件。
//然后，正在收听该事件的观察者将收到该事件的通知。