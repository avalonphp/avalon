<?php
/*
 * Avalon
 * Copyright 2011-2015 Jack P.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Avalon\Database\Model;

/**
 * Secure password model trait.
 *
 * @author Jack P.
 */
trait SecurePassword
{
    /**
     * Crypts the users password.
     */
    public function preparePassword()
    {
        $this->{$this->securePasswordField} = crypt(
            $this->{$this->securePasswordField},
            '$2y$10$' . sha1(microtime() . rand(0, 5000)) . '$'
        );
    }

    /**
     * Authenticates the password with the users current password.
     *
     * @param string $password
     *
     * @return boolean
     */
    public function authenticate($password)
    {
        return $this->{$this->securePasswordField} === crypt($password, $this->{$this->securePasswordField});
    }

    /**
     * Sets and crypts the new password.
     *
     * @param string $newPassword
     */
    public function setPassword($newPassword)
    {
        $this->{$this->securePasswordField} = $newPassword;
        $this->preparePassword();
    }
}
