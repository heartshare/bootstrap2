<?php

namespace app\models\forms;

use Yii;
use app\models\User;

class LoginForm extends \yii\base\Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private $user = false;
    
    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required'],
            
            ['email', 'email'],
            
            ['rememberMe', 'boolean'],

            ['password', 'validatePassword'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return (new User())->attributeLabels() + [
            'rememberMe' => Yii::t('app', 'Remember me')
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute The attribute currently being validated.
     * @param array $params The additional name-value pairs given in the rule.
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError('password', Yii::t('app', 'Incorrect email or password'));
            } elseif ($user && !$user->isActive()) {
                $this->addError('password', $user->getStatusDescription());
            }
        }
    }

    /**
     * Logs in a user using the provided email and password.
     *
     * @return boolean Whether the user is logged in successfully.
     */
    public function login()
    {
        if ($this->validate()) {
            return $this->getUser()->authorize($this->rememberMe);
        } else {
            return false;
        }
    }

    /**
     * Finds user by [[email]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->user === false) {
            $this->user = User::findByEmail($this->email);
        }

        return $this->user;
    }
}
