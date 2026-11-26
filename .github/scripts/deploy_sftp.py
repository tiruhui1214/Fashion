"""Upload tracked application files; never mirror/delete remote content or run SQL."""
from pathlib import Path, PurePosixPath
import os
import posixpath
import subprocess
import tempfile

ROOT_FILES = {"index.php", "setup.php", ".htaccess", "config.example.php", "ai.example.php"}
APP_DIRS = {"app", "assets", "database"}
PRIVATE_NAMES = {"config.local.php", "ai.local.php", ".installed", "sftp.json"}


def allowed(path):
    p = PurePosixPath(path)
    if p.is_absolute() or ".." in p.parts or not p.parts:
        return False
    if any(part in PRIVATE_NAMES or part in {".git", ".vscode", "uploads"} for part in p.parts):
        return False
    if p.suffix.lower() in {".log", ".zip", ".bak"}:
        return False
    return path in ROOT_FILES or (len(p.parts) > 1 and p.parts[0] in APP_DIRS)


def tracked_files():
    data = subprocess.check_output(["git", "ls-files", "-z"])
    result = []
    for item in data.decode("utf-8").split("\0"):
        if item and allowed(item):
            local = Path(item)
            if local.is_symlink() or any(parent.is_symlink() for parent in local.parents):
                raise RuntimeError("Symlinks are not permitted in deployment")
            if not local.is_file():
                raise RuntimeError("A tracked deployment file is missing")
            result.append(item)
    if "index.php" not in result or "app/bootstrap.php" not in result:
        raise RuntimeError("Run deployment from the Fashion repository root")
    return sorted(result, key=lambda x: (x == "index.php", x))


def main():
    import paramiko

    required = ["DEPLOY_HOST", "DEPLOY_USER", "DEPLOY_PASSWORD", "DEPLOY_PATH", "DEPLOY_KNOWN_HOSTS"]
    if any(not os.environ.get(key, "").strip() for key in required):
        raise RuntimeError("Missing deployment secrets; follow README automatic deployment setup")
    remote = os.environ["DEPLOY_PATH"].rstrip("/")
    if not remote.startswith("/") or ".." in PurePosixPath(remote).parts or len(PurePosixPath(remote).parts) < 4:
        raise RuntimeError("DEPLOY_PATH must be the absolute existing website directory")
    files = tracked_files()
    revision = os.environ.get("DEPLOY_REVISION", "manual")[:12]
    if not revision.isalnum():
        raise RuntimeError("Invalid revision")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.RejectPolicy())
    with tempfile.TemporaryDirectory() as temp:
        known = Path(temp) / "known_hosts"
        known.write_text(os.environ["DEPLOY_KNOWN_HOSTS"].strip() + "\n", encoding="utf-8")
        client.load_host_keys(str(known))
        try:
            client.connect(os.environ["DEPLOY_HOST"], port=22,
                           username=os.environ["DEPLOY_USER"], password=os.environ["DEPLOY_PASSWORD"],
                           look_for_keys=False, allow_agent=False, timeout=20, banner_timeout=20, auth_timeout=20)
            with client.open_sftp() as sftp:
                sftp.get_channel().settimeout(60)
                # Refuse a wrong/uninitialised destination before uploading anything.
                for name in ("index.php", "config.local.php", ".installed"):
                    sftp.stat(posixpath.join(remote, name))
                directories = {remote}
                for file in files:
                    destination = posixpath.join(remote, file)
                    parent = posixpath.dirname(destination)
                    relative = PurePosixPath(file).parent.parts
                    current = remote
                    for part in relative:
                        current = posixpath.join(current, part)
                        if current not in directories:
                            try:
                                sftp.stat(current)
                            except FileNotFoundError:
                                sftp.mkdir(current)
                            directories.add(current)
                    temporary = posixpath.join(parent, "." + posixpath.basename(file) + ".deploy-" + revision + ".tmp")
                    try:
                        sftp.put(file, temporary, confirm=True)
                        sftp.chmod(temporary, 0o644)
                        # alwaysdata uses OpenSSH; atomic replacement avoids partial individual files.
                        sftp.posix_rename(temporary, destination)
                    except Exception:
                        try:
                            sftp.remove(temporary)
                        except OSError:
                            pass
                        raise
            print(f"Uploaded {len(files)} application files for {revision}. No remote deletion or database migration.")
        finally:
            client.close()


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        # Keep provider/account/path details out of public Actions logs.
        print(f"Deployment failed ({type(exc).__name__}). Check secrets, verified host key, permissions and remote path.")
        raise SystemExit(1)
