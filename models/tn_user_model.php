<?php
class TN_UserModel extends TN_Model
{
    protected $table = 'tn_users';
    protected $primaryKey = 'id';
    protected $timestamps = true;

    protected $fillable = [
        'name','email','password','role','status','last_login_at',
        'created_at','updated_at'
    ];

    public function findByEmail(string $email, array $columns = ['*'])
    {
        try { return $this->findWhere(['email' => $email], $columns); }
        catch (Throwable $e) { return null; }
    }

    public function checkCredentials(string $email, string $password)
    {
        $user = $this->findByEmail($email);
        if (!$user || empty($user['password'])) return false;
        return password_verify($password, $user['password']) ? $user : false;
    }

    public function createUser(array $data)
    {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }
}
