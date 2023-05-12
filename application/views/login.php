<div class="row text-centered">
    <div class="col-4"></div>
    <div class="col-4">
        <form action="AutenticacionController/autenticar" method="POST">
            <h1 class="h3 mb-3 fw-normal">Restaurant siglo 21</h1>

            <div class="form-floating">
                <input type="email" name="email" class="form-control" id="floatingInput" placeholder="Email">
                <label for="floatingInput">Email</label>
            </div>
            <div class="form-floating">
                <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Contraseña">
                <label for="floatingPassword">Contraseña</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Iniciar Sesión</button>
            <p class="mt-5 mb-3 text-muted"></p>
        </form>
    </div>
    <div class="col-4"></div>
</div>


