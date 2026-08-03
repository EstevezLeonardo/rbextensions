 $nome = $_POST['nome'];
    $sobrenome = $_POST['sobrenome'];
    $email = $_POST['email'];
    $datanascimento = $_POST['datanascimento'];

    $usuario = new \App\Usuario();
    $usuario->setNome($nome);
    $usuario->setSobrenome($sobrenome);
    $usuario->setEmail($email);
    $usuario->setDataNascimento($datanascimento);

    $usuario->salvar();